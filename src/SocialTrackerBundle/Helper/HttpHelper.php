<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 19.02.18
 * Time: 20:20
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Helper;

/**
 * Methods for http responses and requests
 */
class HttpHelper
{
    /**
     * Validate post url
     *
     * @param string $url Url value
     *
     * @return bool
     */
    public static function validUrl(string $url): bool
    {
        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);
        curl_exec($handle);

        if (in_array((int) curl_getinfo($handle, CURLINFO_HTTP_CODE), [404, 0])) {
            return false;
        }
        curl_close($handle);

        return true;
    }
}
