<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 07.01.18
 * Time: 22:48
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Recording\Event\Validator;

/**
 * Interface SocialValidateInterface
 */
interface SocialValidateInterface
{
    /**
     * Validate social account
     *
     * @param string $userName
     *
     * @return bool
     */
    public function validateUserAccount(string $userName): bool;
}
