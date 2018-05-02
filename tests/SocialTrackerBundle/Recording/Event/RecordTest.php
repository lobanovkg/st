<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 01.03.18
 * Time: 17:03
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Recording\Event;

use Doctrine\ORM\EntityManager;
use SocialTrackerBundle\Recording\Event\EventData;
use SocialTrackerBundle\Recording\Event\Record;
use Tests\SocialTrackerBundle\AbstractSocialTrackerDatabaseTestCase;
use Tests\SocialTrackerBundle\Helper\DataSet\ClearDatabaseDataSet;

/**
 * Class RecordTest
 * @group social-tracker
 */
class RecordTest extends AbstractSocialTrackerDatabaseTestCase
{
    /** Data provider trait */
    use RecordDataProviderTrait;

    /** @var Record Event data record */
    private static $eventRecord;

    /** @var EntityManager Doctrine entity manager */
    private static $em;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        self::$em          = self::$container->get('doctrine.orm.entity_manager');
        self::$eventRecord = new Record(self::$em, self::$container);
    }

    /**
     * Get data set
     *
     * @return \PHPUnit\DbUnit\DataSet\YamlDataSet
     */
    public function getDataSet()
    {
        return ClearDatabaseDataSet::getDataSet();
    }

    /**
     * Testing insert/update live event
     *
     * @param array $eventDataArray
     *
     * @dataProvider insertUpdateLiveEventDataProvider
     *
     * @covers Record::record()
     */
    public function testInsertUpdateLiveEventRecord(array $eventDataArray)
    {
        foreach ($eventDataArray as $eventData) {
            $eventData = $eventData[0];

            self::$eventRecord->record($eventData);

            self::assertEquals(1, $this->getConnection()->getRowCount('live_event'));

            $eventEntity = self::$em->getRepository('SocialTrackerBundle:LiveEvent')->findOneBy(['originId' => $eventData->getId()]);

            self::assertEquals($eventEntity->getActive(), $eventData->getActive());
            self::assertEquals($eventEntity->getName(), $eventData->getName());
        }
    }

    /**
     * Testing insert new account, socila type, event accounts relation
     *
     * @param array $dataSet
     *
     * @dataProvider insertSocialInfoDataProvider
     *
     * @covers Record::record()
     */
    public function testInsertSocialData(array $dataSet)
    {
        /**
         * @var array $data
         * Example format:
         * array(
         *  'count',
         *  new EventData(),
         * )
         */
        foreach ($dataSet as $data) {
            self::$eventRecord->record($data[1]);

            self::assertEquals($data[0], $this->getConnection()->getRowCount('social_account'));
            self::assertEquals($data[0], $this->getConnection()->getRowCount('social_type'));
            self::assertEquals($data[0], $this->getConnection()->getRowCount('live_event_accounts'));
        }
    }
}
