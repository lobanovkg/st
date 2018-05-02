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
 * Class LiveEventAccounts repository
 */
class LiveEventAccounts extends EntityRepository
{
    /**
     * Delete all rows by origin event id
     *
     * @param int $originEventId Social event id
     */
    public function deleteAllRowsByOriginEventId(int $originEventId)
    {
        $connection = $this->_em->getConnection();
        $connection->executeUpdate(
            'delete lea from live_event_accounts lea
                      join live_event e on e.id = lea.live_event_id
                   where e.active = 1 AND e.origin_id = '.$originEventId
        );
    }

    /**
     * Delete row by origin event id and account id
     *
     * @param int   $originEventId Social event id
     * @param array $accountIds    Social account id
     */
    public function deleteRowByOriginEventIdAndAccountIds(int $originEventId, array $accountIds)
    {
        $accountIds = implode(',', $accountIds);

        $connection = $this->_em->getConnection();
        $connection->executeUpdate(
            'delete lea from live_event_accounts lea
                      join live_event e on e.id = lea.live_event_id
                   where e.active = 1 AND e.origin_id = '.$originEventId.' AND lea.account_id in ('.$accountIds.')'
        );
    }

    /**
     * Get active social account id
     *
     * @return array
     */
    public function getActiveAccountIds()
    {
        $result = $this->_em->createQueryBuilder()
            ->select('distinct ea.accountId')
            ->from($this->_entityName, 'ea')
            ->join('ea.liveEvent', 'e')
            ->where('e.active = :active')
            ->setParameter('active', LiveEvent::STATUS_ACTIVE)
            ->getQuery()
            ->getArrayResult();

        return array_map('current', $result);
    }

    /**
     * Check account for active event
     *
     * @param int $accountId Social account
     *
     * @return bool
     */
    public function hasActiveEvents(int $accountId): bool
    {
        $result = $this->_em->createQueryBuilder()
            ->select('count(e.id)')
            ->from($this->_entityName, 'ea')
            ->join('ea.liveEvent', 'e')
            ->where('e.active = :active')
            ->andWhere('ea.accountId = :accountId')
            ->setParameter('active', LiveEvent::STATUS_ACTIVE)
            ->setParameter('accountId', $accountId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
