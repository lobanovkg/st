<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 08.01.18
 * Time: 17:58
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class LiveEventPosts repository
 */
class LiveEventPosts extends EntityRepository
{
    /**
     * Delete all live event post relation by event
     *
     * @param int $originEventId Live event id
     */
    public function deleteAllRowsByOriginEventId(int $originEventId)
    {
        $connection = $this->_em->getConnection();
        $connection->executeUpdate(
            'delete ep from live_event_posts ep
                      join social_post p on p.id = ep.post_id
                      join live_event e on e.id = ep.live_event_id
                    where e.active = 1 AND e.origin_id = '.$originEventId
        );
    }

    /**
     * Delete live event post relation by event and account ids
     *
     * @param int   $originEventId Live event id
     * @param array $accountIds    Social account ids
     */
    public function deleteRowByOriginEventIdAndAccountIds(int $originEventId, array $accountIds)
    {
        $accountIds = implode(',', $accountIds);

        $connection = $this->_em->getConnection();
        $connection->executeUpdate(
            'delete ep from live_event_posts ep
                      join social_post p on p.id = ep.post_id
                      join live_event e on e.id = ep.live_event_id
                    where e.active = 1 AND e.origin_id = '.$originEventId.' AND p.account_id in ('.$accountIds.')'
        );
    }
}
