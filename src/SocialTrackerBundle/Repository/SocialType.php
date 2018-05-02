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
 * Class SocialType repository
 */
class SocialType extends EntityRepository
{
    const SOCIAL_TYPE_NAME_INSTAGRAM = 'in';
    const SOCIAL_TYPE_NAME_TWITTER = 'tw';
    const SOCIAL_TAG_NAME = 'tag';

    /**
     * Get social type name by account id
     *
     * @param int $accountId Social account id
     *
     * @return string
     */
    public function getSocialTypeNameByAccountId(int $accountId): string
    {

        $connection = $this->_em->getConnection();
        $result     = $connection->createQueryBuilder()
            ->select('st.name')
            ->from('social_account', 'a')
            ->join('a', 'social_type', 'st', 'st.id = a.social_type_id')
            ->where('a.id = :accountId')
            ->setParameter('accountId', $accountId)
            ->execute()
            ->fetchAll(\PDO::FETCH_COLUMN);

        return reset($result) ?: '';
    }
}
