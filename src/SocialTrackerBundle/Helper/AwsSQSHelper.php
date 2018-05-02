<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 13.02.18
 * Time: 18:59
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Helper;

/**
 * Class AwsSQSHelper
 */
class AwsSQSHelper
{
    /**
     * Checking queue url of type fifo
     *
     * @param string $queueUrl AWS SQS queue url
     *
     * @return bool
     */
    public static function isQueueTypeFifo(string $queueUrl): bool
    {
        return (bool) preg_match('#.*\.fifo#', $queueUrl);
    }
}
