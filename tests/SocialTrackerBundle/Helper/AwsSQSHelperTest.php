<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 01.03.18
 * Time: 20:18
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Helper;

use PHPUnit\Framework\TestCase;
use SocialTrackerBundle\Helper\AwsSQSHelper;

/**
 * Class AwsSQSHelper
 *
 * @group social-tracker
 */
class AwsSQSHelperTest extends TestCase
{
    /**
     * @covers AwsSQSHelper::isQueueTypeFifo()
     */
    public function testIsQueueTypeFifo()
    {
        $testQueueUrl = 'https://sqs.us-east-1.amazonaws.com/123456789/social-tracker-fifo-comment-grabber-dev';
        self::assertFalse(AwsSQSHelper::isQueueTypeFifo($testQueueUrl));

        $testQueueUrl = 'https://sqs.us-east-1.amazonaws.com/123456789/social-tracker-fifo-comment-grabber-dev.fifo';
        self::assertTrue(AwsSQSHelper::isQueueTypeFifo($testQueueUrl));
    }
}
