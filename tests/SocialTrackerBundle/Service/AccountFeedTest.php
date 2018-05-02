<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 27.02.18
 * Time: 15:16
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle;

use SocialTrackerBundle\Service\AccountFeed;
use Symfony\Component\DependencyInjection\Container;
use Tests\SocialTrackerBundle\Helper\DataSet\AccountFeedDataSet;

/**
 * Class AccountFeedTest
 * @group social-tracker
 */
class AccountFeedTest extends AbstractSocialTrackerDatabaseTestCase
{
    /**
     * Get data set
     *
     * @return \PHPUnit\DbUnit\DataSet\YamlDataSet
     */
    public function getDataSet()
    {
        return AccountFeedDataSet::getDataSet();
    }

    /**
     * Testing requests for AccountFeed
     *
     * @covers AccountFeed::getPostsByOriginEventIdAndAccounts()
     */
    public function testAccountFeed()
    {
        $accountFeedService = self::$container->get('social_tracker.feed.service');

        $result = $accountFeedService->getPostsByOriginEventIdAndAccounts(777, []);
        self::assertCount(2, $result);

        $result = $accountFeedService->getPostsByOriginEventIdAndAccounts(777, [], 'master', 1);
        self::assertCount(1, $result);

        $result = $accountFeedService->getPostsByOriginEventIdAndAccounts(777, ['in' => ['test-instagram']]);
        self::assertCount(1, $result);

        $result = $accountFeedService->getPostsByOriginEventIdAndAccounts(777, ['tw' => ['test-twitter']]);
        self::assertCount(1, $result);

        $result = $accountFeedService->getPostsByOriginEventIdAndAccounts(777, ['tag' => ['mega', 'test']]);
        self::assertCount(2, $result);

        $result = $accountFeedService->getPostsByOriginEventIdAndAccounts(777, ['tw' => ['test-twitter'], 'tag' => ['test']]);
        self::assertCount(0, $result);

        $result = $accountFeedService->getPostsByOriginEventIdAndAccounts(777, ['tw' => ['test-twitter'], 'tag' => ['mega']]);
        self::assertCount(1, $result);
    }
}
