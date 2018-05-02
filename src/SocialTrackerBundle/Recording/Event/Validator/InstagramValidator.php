<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 07.01.18
 * Time: 23:31
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Recording\Event\Validator;

/**
 * Instagram account validator
 */
class InstagramValidator implements SocialValidateInterface
{
    /**
     * Validate social account
     *
     * @param string $userName
     *
     * @return bool
     */
    public function validateUserAccount(string $userName): bool
    {
        $url    = 'https://www.instagram.com/'.$userName.'/?__a=1';
        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handle);

        if (200 !== (int) curl_getinfo($handle, CURLINFO_HTTP_CODE)) {
            return false;
        }

        curl_close($handle);

        $accountInfo = json_decode($response, true);
        if ($this->isValidAccount($accountInfo)) {
            return false;
        }

        return true;
    }

    /**
     * Return valid result
     *
     * @param array $accountInfo
     *
     * @return bool
     */
    private function isValidAccount(array $accountInfo): bool
    {
        if (!isset($accountInfo['graphql']['user']) || !isset($accountInfo['graphql']['user']['is_private']) || $accountInfo['graphql']['user']['is_private']) {
            return true;
        }

        return false;
    }
}
