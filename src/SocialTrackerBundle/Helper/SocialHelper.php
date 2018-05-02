<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 05.02.18
 * Time: 23:03
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Helper;

/**
 * Social Helper
 */
class SocialHelper
{
    /**
     * Parse from message only hashtags
     *
     * @param string $message Post message
     *
     * @return array
     */
    public static function parseHashTagsFromString(string $message): array
    {
        preg_match_all('/[#][a-z0-9_]+/i', $message, $match);

        if (isset($match[0]) && is_array($match[0]) && count($match[0])) {
            return array_map(
                function ($hashtag) {
                    return str_replace('#', '', strtolower($hashtag));
                },
                $match[0]
            );
        }

        return [];
    }
}
