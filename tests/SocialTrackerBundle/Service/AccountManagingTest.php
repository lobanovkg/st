<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 28.02.18
 * Time: 17:01
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Service;

use SocialTrackerBundle\Service\AccountManaging;
use Tests\SocialTrackerBundle\AbstractSocialTrackerDatabaseTestCase;
use Tests\SocialTrackerBundle\Helper\DataSet\AccountManagingDataSet;

/**
 * Class AccountManagingTest
 * @group social-tracker
 */
class AccountManagingTest extends AbstractSocialTrackerDatabaseTestCase
{
    /** @var AccountManaging Delete, update, insert social account */
    private static $accountManagingService;

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$accountManagingService = self::$container->get('social_tracker.account_managing.service');
    }

    /**
     * Get data set
     *
     * @return \PHPUnit\DbUnit\DataSet\YamlDataSet
     */
    public function getDataSet()
    {
        return AccountManagingDataSet::getDataSet();
    }

    /**
     * Testing delete all event relations
     *
     * @covers AccountManaging::deleteAllEventRelations()
     */
    public function testDeleteAllEventRelations()
    {
        self::assertEquals(5, $this->getConnection()->getRowCount('live_event_accounts'));
        self::assertEquals(5, $this->getConnection()->getRowCount('live_event_posts'));

        self::$accountManagingService->deleteAllEventRelations(777);

        self::assertEquals(3, $this->getConnection()->getRowCount('live_event_accounts'));
        self::assertEquals(3, $this->getConnection()->getRowCount('live_event_posts'));

        self::$accountManagingService->deleteAllEventRelations(666);

        self::assertEquals(3, $this->getConnection()->getRowCount('live_event_accounts'));
        self::assertEquals(3, $this->getConnection()->getRowCount('live_event_posts'));
    }

    /**
     * Testing delete not used social account relation for live event
     *
     * @covers AccountManaging::deleteEventRelationExceptExistingAccount()
     */
    public function testDeleteEventRelationExceptExistingAccount()
    {
        self::assertEquals(5, $this->getConnection()->getRowCount('live_event_accounts'));
        self::assertEquals(5, $this->getConnection()->getRowCount('live_event_posts'));

        self::$accountManagingService->deleteEventRelationExceptExistingAccount(777, ['in' => ['test-instagram']]);

        self::assertEquals(4, $this->getConnection()->getRowCount('live_event_accounts'));
        self::assertEquals(4, $this->getConnection()->getRowCount('live_event_posts'));
    }
}
