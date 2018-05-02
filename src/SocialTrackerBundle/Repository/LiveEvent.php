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
 * Class LiveEvent repository
 */
class LiveEvent extends EntityRepository
{
    const STATUS_ACTIVE = 1;
}
