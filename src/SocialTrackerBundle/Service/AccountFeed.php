<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 17.01.18
 * Time: 21:47
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Service;

use SocialTrackerBundle\Repository\SocialType;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class AccountFeed consist feed methods
 */
class AccountFeed
{
    /**
     * @var Container Symfony DI
     */
    private $container;

    /**
     * AccountFeed constructor.
     *
     * @param Container $container Symfony DI
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get posts by origin event id and filtering by accounts, hashtags
     *
     * @param int    $originEventId  Origin live event id
     * @param array  $accounts       Account data for query
     * @param string $connectionType Mysql db connection type
     * @param int    $limit          Result limit
     *
     * @return array
     */
    public function getPostsByOriginEventIdAndAccounts(int $originEventId, array $accounts, $connectionType = 'slave', $limit = 50): array
    {
        $connection = $this->container->get('database_connection');
        $connection->connect($connectionType);
        $qb = $connection->createQueryBuilder()
            ->select(
                'p.origin_post_id origin_id',
                'p.url',
                'p.message',
                'p.publish_date timestamp',
                'st.name social',
                'a.user_name username',
                'p.post_info images'
            )->from('live_event_posts', 'ep')
            ->join('ep', 'social_post', 'p', 'ep.post_id = p.id')
            ->join('ep', 'live_event', 'e', 'ep.live_event_id = e.id')
            ->join('p', 'social_account', 'a', 'a.id = p.account_id')
            ->join('a', 'social_type', 'st', 'a.social_type_id = st.id')
            ->where('e.origin_id = :eventId')
            ->setParameter('eventId', $originEventId);

        /** Query part for Twitter accounts if it`s exist */
        if (isset($accounts[SocialType::SOCIAL_TYPE_NAME_TWITTER]) && $accounts[SocialType::SOCIAL_TYPE_NAME_TWITTER]) {
            $qb->andWhere('st.name = :twitterName and a.user_name in (:twitterAccounts)')
                ->setParameter('twitterName', SocialType::SOCIAL_TYPE_NAME_TWITTER)
                ->setParameter('twitterAccounts', $accounts[SocialType::SOCIAL_TYPE_NAME_TWITTER], \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        }

        /** Query part for Instagram accounts if it`s exist */
        if (isset($accounts[SocialType::SOCIAL_TYPE_NAME_INSTAGRAM]) && $accounts[SocialType::SOCIAL_TYPE_NAME_INSTAGRAM]) {
            $qb->andWhere('st.name = :instagramName and a.user_name in (:instagramAccounts)')
                ->setParameter('instagramName', SocialType::SOCIAL_TYPE_NAME_INSTAGRAM)
                ->setParameter('instagramAccounts', $accounts[SocialType::SOCIAL_TYPE_NAME_INSTAGRAM], \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        }

        /** Query part for social hashtags if it`s exist */
        if (isset($accounts[SocialType::SOCIAL_TAG_NAME]) && $accounts[SocialType::SOCIAL_TAG_NAME]) {
            $qb->join('p', 'social_post_tag', 'spt', 'spt.post_id = p.id')
                ->join('spt', 'social_tag', 'tag', 'tag.id = spt.tag_id')
                ->andWhere('tag.name in (:tags)')
                ->setParameter('tags', $accounts[SocialType::SOCIAL_TAG_NAME], \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        }

        return $qb->orderBy('p.publish_date', 'desc')
            ->setMaxResults($limit)
            ->groupBy('p.id')
            ->execute()
            ->fetchAll();
    }
}
