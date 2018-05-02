<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 28.02.18
 * Time: 23:40
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Recording\Event;

use SocialTrackerBundle\Recording\Event\EventData;
use SocialTrackerBundle\Recording\Event\EventDataService;
use SocialTrackerBundle\Repository\SocialType;
use Tests\SocialTrackerBundle\AbstractSocialTrackerDatabaseTestCase;
use Tests\SocialTrackerBundle\Helper\DataSet\EventDataServiceDataSet;

/**
 * Class EventDataServiceTest
 *
 * @group social-tracker
 */
class EventDataServiceTest extends AbstractSocialTrackerDatabaseTestCase
{
    /** Data provider trait */
    use EventDataServiceDataProviderTrait;

    /** @var EventDataService Event data service */
    private static $eventDataService;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $em                     = self::$container->get('doctrine.orm.entity_manager');
        self::$eventDataService = new EventDataService($em);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        self::$eventDataService = null;
    }

    /**
     * Get EventData, data set
     *
     * @return \PHPUnit\DbUnit\DataSet\YamlDataSet
     */
    public function getDataSet()
    {
        return EventDataServiceDataSet::getDataSet();
    }

    /**
     * Testing EventDataService with exist account
     *
     * @param EventData $eventData
     *
     * @dataProvider getExistEventData
     *
     * @covers       EventDataService::getNewSocialAccounts()
     * @covers       EventDataService::getExistSocialAccounts()
     */
    public function testEventDataServiceExistAccount(EventData $eventData)
    {
        self::$eventDataService->setEventData($eventData);

        $newAccounts  = self::$eventDataService->getNewSocialAccounts();
        $existAccount = self::$eventDataService->getExistSocialAccounts();

        self::assertCount(0, $newAccounts);
        self::assertCount(1, $existAccount);
    }

    /**
     * Testing EventDataService with new and exist account
     *
     * @param EventData $eventData
     *
     * @dataProvider getNewAndExistEventData
     *
     * @covers       EventDataService::getNewSocialAccounts()
     * @covers       EventDataService::getExistSocialAccounts()
     */
    public function testEventDataServiceNewAndExistAccounts(EventData $eventData)
    {
        self::$eventDataService->setEventData($eventData);

        $newAccounts  = self::$eventDataService->getNewSocialAccounts();
        $existAccount = self::$eventDataService->getExistSocialAccounts();

        self::assertCount(1, $newAccounts);
        self::assertCount(1, $existAccount);
    }

    /**
     * Testing EventDataService with new account
     *
     * @param EventData $eventData
     *
     * @dataProvider getNewEventData
     *
     * @covers       EventDataService::getNewSocialAccounts()
     * @covers       EventDataService::getExistSocialAccounts()
     */
    public function testEventDataServiceNewAccount(EventData $eventData)
    {
        self::$eventDataService->setEventData($eventData);

        $newAccounts  = self::$eventDataService->getNewSocialAccounts();
        $existAccount = self::$eventDataService->getExistSocialAccounts();

        self::assertCount(1, $newAccounts);
        self::assertCount(0, $existAccount);
    }

    /**
     * Testing get clone EventData for social accounts and EventData
     *
     * @param array     $accounts
     * @param EventData $eventData
     *
     * @dataProvider getAccountsAndEventData
     *
     * @covers       EventDataService::getCloneEventData()
     */
    public function testGetCloneEventData(array $accounts, EventData $eventData)
    {
        $clonedEventData = self::$eventDataService->getCloneEventData($accounts, $eventData);

        self::assertCount(count($clonedEventData->getAccounts()), $accounts);
    }

    /**
     * Testing get json row from EventData
     *
     * @param EventData $eventData
     *
     * @dataProvider getNewAndExistEventData
     *
     * @covers       EventDataService::getJsonRowAccount()
     */
    public function testGetJsonRowAccount(EventData $eventData)
    {
        $jsonEventData = self::$eventDataService->getJsonRowAccount($eventData);

        self::assertCount(2, $jsonEventData);

        foreach ($jsonEventData as $json) {
            self::assertJson($json);
        }
    }

    /**
     * Testing get social type from EventData
     *
     * @param EventData $eventData
     *
     * @dataProvider getNewAndExistEventData
     *
     * @covers       EventDataService::getSocialType()
     */
    public function testGetSocialType(EventData $eventData)
    {
        $socialTypes = self::$eventDataService->getSocialType($eventData);

        self::assertCount(1, $socialTypes);
    }

    /**
     * Testing get social user name from EventData
     *
     * @param EventData $eventData
     *
     * @dataProvider getNewAndExistEventData
     *
     * @covers       EventDataService::getSocialUserName()
     */
    public function testGetSocialUserName(EventData $eventData)
    {
        $socialUserNames = self::$eventDataService->getSocialUserName($eventData);

        self::assertCount(1, $socialUserNames);
        self::assertCount(2, $socialUserNames[SocialType::SOCIAL_TYPE_NAME_INSTAGRAM]);
    }
}
