<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 06.03.18
 * Time: 20:06
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Helper;

/**
 * Class ArrayHelper
 */
class ArrayHelper
{
    /**
     * Getting random value from assoc arrays
     *
     * @param array $array The input array
     * @param int   $num   Specifies how many entries should be picked
     *
     * @return array
     */
    public static function arrayRandomAssoc(array $array, int $num = 1): array
    {
        $keys = array_keys($array);
        shuffle($keys);

        $r = [];
        for ($i = 0; $i < $num; $i++) {
            $r[$keys[$i]] = $array[$keys[$i]];
        }

        return $r;
    }
}
