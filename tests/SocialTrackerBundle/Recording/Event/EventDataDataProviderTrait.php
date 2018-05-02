<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 28.02.18
 * Time: 18:55
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Recording\Event;

use SocialTrackerBundle\Recording\Event\EventData;
use SocialTrackerBundle\Repository\SocialType;

/**
 * Class EventDataTrait
 */
trait EventDataDataProviderTrait
{
    /**
     * Get not valid event data
     *
     * @return array
     */
    public function getNotValidEventData()
    {
        $eventData1 = new EventData();
        $eventData1->setName('Test event 1')
            ->setActive(1)
            ->setId(111)
            ->setAccounts(
                [
                    [
                        EventData::ACCOUNT_TYPE_KEY => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                    ],
                ]
            );

        $eventData2 = new EventData();
        $eventData2->setName('Test event 2')
            ->setActive(1)
            ->setId(222)
            ->setAccounts(
                [
                    [
                        EventData::ACCOUNT_TYPE_KEY      => 'fb',
                        EventData::ACCOUNT_USER_NAME_KEY => 'test-instagram-account',
                    ],
                ]
            );

        $eventData3 = new EventData();
        $eventData3->setName('Test event 3')
            ->setActive(1)
            ->setId(333)
            ->setAccounts(
                [
                    [
                        'test'       => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                        'index-test' => 'test-instagram-account',
                    ],
                ]
            );

        $eventData4 = new EventData();
        $eventData4->setName('Test event 4')
            ->setActive(1)
            ->setId(444)
            ->setAccounts([]);

        return [[$eventData1], [$eventData2], [$eventData3], [$eventData4]];
    }

    /**
     * Get valid event data
     *
     * @return array
     */
    public function getValidEventData(): array
    {
        $eventData = new EventData();
        $eventData->setName('Test event')
            ->setActive(1)
            ->setId(555)
            ->setAccounts(
                [
                    [
                        EventData::ACCOUNT_TYPE_KEY      => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                        EventData::ACCOUNT_USER_NAME_KEY => 'test-instagram-account',
                    ],
                ]
            );

        return [[$eventData]];
    }
}
