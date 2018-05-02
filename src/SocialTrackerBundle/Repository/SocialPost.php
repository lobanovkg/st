<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 04.01.18
 * Time: 23:16
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class SocialPost repository
 */
class SocialPost extends EntityRepository
{
    const FIELD_NAME_ID = 'id';
    const FIELD_NAME_ORIGIN_POST_ID = 'originPostId';

    /**
     * Delete post from db
     *
     * @param int $postId Social post id
     *
     * @return int
     */
    public function deletePostById(int $postId): int
    {
        $connection = $this->_em->getConnection();

        return $connection->delete('social_post', ['id' => $postId]);
    }

    /**
     * Find posts by LiveEvent id.
     *
     * @param int $eventId Live event id
     * @param int $limit   Result limit
     *
     * @return array
     */
    public function findByEvent($eventId, $limit = 50)
    {
        $liveEventPostRepository = $this->_em->getRepository('SocialTrackerBundle:LiveEventPosts');

        return $this->_em->createQueryBuilder()
            ->select('p')->from($liveEventPostRepository->getEntityName(), 'ep')
            ->join($this->_entityName, 'p', 'with', 'ep.postId = p.id')
            ->join('ep.liveEvent', 'e')
            ->where('e.id = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('p.publishDate', 'desc')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get post field by origin post ids and account id
     *
     * @param int    $accountId     Social account id
     * @param array  $originPostIds Origin post ids
     * @param string $field         Field for select in social_post table
     *
     * @return array
     */
    public function getPostIdsByOriginPostIdAndAccountId(int $accountId, array $originPostIds, string $field)
    {
        $qb = $this->_em->createQueryBuilder();

        if (self::FIELD_NAME_ID === $field) {
            $qb->select('p.id');
        } elseif (self::FIELD_NAME_ORIGIN_POST_ID === $field) {
            $qb->select('distinct p.originPostId');
        } else {
            return [];
        }

        $result = $qb->from($this->_entityName, 'p')
            ->where('p.accountId = :accountId')
            ->andWhere('p.originPostId in (:originPostIds)')
            ->setParameter('accountId', $accountId)
            ->setParameter('originPostIds', $originPostIds)
            ->getQuery()->getArrayResult();


        return array_map('current', $result);
    }

    /**
     * Get Posts by date range
     *
     * @param string $dateFrom Date from
     * @param string $dateTo   Date to
     *
     * @return array
     */
    public function getPostIdsByDateRange(string $dateFrom, string $dateTo)
    {
        $connection = $this->_em->getConnection();

        /**
         * Create query for posts by range
         */
        return $connection->createQueryBuilder()
            ->select('id')
            ->from('social_post')
            ->where('publish_date >= :dateTo')
            ->andWhere('publish_date < :dateFrom')
            ->setParameter('dateFrom', $dateFrom)
            ->setParameter('dateTo', $dateTo)
            ->execute()
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @param int   $originEventId Origin event id
     * @param array $accounts      Social account info
     * @param int   $limit         Result limit
     *
     * @return array
     */
    public function getPostsByOriginEventIdAndAccounts($originEventId, array $accounts, $limit = 50)
    {
        $liveEventPostRepository = $this->_em->getRepository('SocialTrackerBundle:LiveEventPosts');
        $socialAccountRepository = $this->_em->getRepository('SocialTrackerBundle:SocialAccount');

        $qb = $this->_em->createQueryBuilder()
            ->select(
                'p.originPostId origin_id',
                'p.url',
                'p.message',
                'p.publishDate timestamp',
                'st.name social',
                'a.userName username',
                'p.postInfo images'
            )->from($liveEventPostRepository->getEntityName(), 'ep')
            ->join($this->_entityName, 'p', 'with', 'ep.postId = p.id')
            ->join('ep.liveEvent', 'e')
            ->join($socialAccountRepository->getEntityName(), 'a', 'with', 'a.id = p.accountId')
            ->join('a.socialType', 'st')
            ->where('e.originId = :eventId')
            ->setParameter('eventId', $originEventId);

        if (isset($accounts[SocialType::SOCIAL_TYPE_NAME_TWITTER]) && $accounts[SocialType::SOCIAL_TYPE_NAME_TWITTER]) {
            $qb->andWhere('st.name = :twitterName and a.userName in (:twitterAccounts)')
                ->setParameter('twitterName', SocialType::SOCIAL_TYPE_NAME_TWITTER)
                ->setParameter('twitterAccounts', $accounts[SocialType::SOCIAL_TYPE_NAME_TWITTER]);
        }

        if (isset($accounts[SocialType::SOCIAL_TYPE_NAME_INSTAGRAM]) && $accounts[SocialType::SOCIAL_TYPE_NAME_INSTAGRAM]) {
            $qb->andWhere('st.name = :instagramName and a.userName in (:instagramAccounts)')
                ->setParameter('instagramName', SocialType::SOCIAL_TYPE_NAME_INSTAGRAM)
                ->setParameter('instagramAccounts', $accounts[SocialType::SOCIAL_TYPE_NAME_INSTAGRAM]);
        }

        return $qb->orderBy('p.publishDate', 'desc')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Get post info from db by post id
     *
     * @param int $postId Social post id
     *
     * @return array
     */
    public function getPostById(int $postId): array
    {
        $connection = $this->_em->getConnection();

        return $connection->createQueryBuilder()
            ->select('p.id', 'p.url', 'p.post_info', 't.name as socialTypeName', 'p.account_id')
            ->from('social_post', 'p')
            ->join('p', 'social_account', 'a', 'a.id = p.account_id')
            ->join('a', 'social_type', 't', 't.id = a.social_type_id')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId)
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get post info from db by post url
     *
     * @param string $postUrl Post url
     *
     * @return array
     */
    public function getPostByUrl(string $postUrl): array
    {
        $connection = $this->_em->getConnection();

        return $connection->createQueryBuilder()
            ->select('p.id', 'p.url', 'p.post_info', 't.name as socialTypeName', 'p.account_id')
            ->from('social_post', 'p')
            ->join('p', 'social_account', 'a', 'a.id = p.account_id')
            ->join('a', 'social_type', 't', 't.id = a.social_type_id')
            ->where('p.url = :postUrl')
            ->setParameter('postUrl', $postUrl)
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param \SocialTrackerBundle\Entity\SocialPost $post
     */
    public function updateFieldPostInfoByEntity(\SocialTrackerBundle\Entity\SocialPost $post)
    {
        $sql = 'update social_post set post_info = :postInfo where account_id = :accountId and origin_post_id = :originPostId';

        $connection = $this->_em->getConnection();
        $stmt       = $connection->prepare($sql);

        $postInfo     = $post->getPostInfo();
        $accountId    = $post->getAccountId();
        $originPostId = $post->getOriginPostId();

        $stmt->bindParam('postInfo', $postInfo);
        $stmt->bindParam('accountId', $accountId);
        $stmt->bindParam('originPostId', $originPostId);
        $stmt->execute();
    }
}
