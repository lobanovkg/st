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
 * Class SocialAccount Repository
 */
class SocialAccount extends EntityRepository
{
    /**
     * Get accounts for grabbing
     *
     * @param int $accountId Social account id
     *
     * @return array
     */
    public function getAvailableAccountGroupBySocialTypeByAccountId(int $accountId): array
    {
        $result                 = [];
        $eventAccountRepository = $this->_em->getRepository('SocialTrackerBundle:LiveEventAccounts');

        $accounts = $this->_em->createQueryBuilder()
            ->select('a.id', 'a.userName', 'st.name as socialType')
            ->from($this->_entityName, 'a')
            ->join('a.socialType', 'st')
            ->join(
                $eventAccountRepository->getEntityName(),
                'ea',
                'with',
                'ea.accountId = a.id'
            )->join('ea.liveEvent', 'e')
            ->where('e.active = :active')
            ->andWhere('a.id = :accountId')
            ->setParameter('active', LiveEvent::STATUS_ACTIVE)
            ->setParameter('accountId', $accountId)
            ->getQuery()
            ->getArrayResult();

        foreach ($accounts as $account) {
            $result[$account['socialType']][$account['id']] = $account['userName'];
        }

        return $result;
    }

    /**
     * Get used social account for live event
     *
     * @param int   $originEventId Origin live event id
     * @param array $accountInfo   Existing social account name group by social type
     *                             data format:
     *                             array(
     *                             socialTypeName_1 => array(socialAccountName_5, socialAccountName_6, ...),
     *                             socialTypeName_3 => array(socialAccountName_7, socialAccountName_8, ...)
     *                             )
     *
     * @return array
     */
    public function getAccountIdByOriginEventIdAndAccountInfo(int $originEventId, array $accountInfo): array
    {
        if (!count($accountInfo)) {
            return [];
        }

        $eventAccountRepository = $this->_em->getRepository('SocialTrackerBundle:LiveEventAccounts');
        $qb                     = $this->_em->createQueryBuilder()
            ->select('distinct a.id')->from($this->_entityName, 'a')
            ->join(
                $eventAccountRepository->getEntityName(),
                'ea',
                'with',
                'ea.accountId = a.id'
            )->join('ea.liveEvent', 'e')
            ->join('a.socialType', 't')
            ->where('e.originId = :eventId')
            ->setParameter('eventId', $originEventId);

        foreach ($accountInfo as $socialType => $accountNames) {
            $qb->andWhere('t.name = :socialTypeName and a.userName in (:accountNames)')
                ->setParameter('socialTypeName', $socialType)
                ->setParameter('accountNames', $accountNames);
        }

        $result = $qb->getQuery()->getArrayResult();

        return array_map('current', $result);
    }

    /**
     * Get not used social account for live event
     *
     * @param int   $originEventId      Origin live event id
     * @param array $existingAccountIds Existing account id
     *
     * @return array
     */
    public function getNotExistingAccountForEvent(int $originEventId, array $existingAccountIds): array
    {
        if (!count($existingAccountIds)) {
            return [];
        }

        $eventAccountRepository = $this->_em->getRepository('SocialTrackerBundle:LiveEventAccounts');
        $qb                     = $this->_em->createQueryBuilder()
            ->select('distinct a.id')->from($this->_entityName, 'a')
            ->join(
                $eventAccountRepository->getEntityName(),
                'ea',
                'with',
                'ea.accountId = a.id'
            )->join('ea.liveEvent', 'e')
            ->join('a.socialType', 't')
            ->where('e.originId = :eventId')
            ->andWhere('a.id not in (:existingAccountIds)')
            ->setParameter('eventId', $originEventId)
            ->setParameter('existingAccountIds', $existingAccountIds);

        $result = $qb->getQuery()->getArrayResult();

        return array_map('current', $result);
    }
}
