<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 01.03.18
 * Time: 18:25
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Recording\Event;

use SocialTrackerBundle\Recording\Event\EventData;
use SocialTrackerBundle\Repository\SocialType;

/**
 * Class RecordDataProviderTrait
 */
trait RecordDataProviderTrait
{
    /**
     * Get two EventData for one live event with different activity and names
     *
     * @return array
     */
    public function insertUpdateLiveEventDataProvider(): array
    {
        $eventData1 = new EventData();
        $eventData1->setName('test event')
            ->setActive(1)
            ->setId(777)
            ->setAccounts(
                [
                    [
                        EventData::ACCOUNT_TYPE_KEY      => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                        EventData::ACCOUNT_USER_NAME_KEY => 'mega-account',
                    ],
                ]
            );

        $eventData2 = new EventData();
        $eventData2->setName('rewrite event')
            ->setActive(0)
            ->setId(777)
            ->setAccounts(
                [
                    [
                        EventData::ACCOUNT_TYPE_KEY      => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                        EventData::ACCOUNT_USER_NAME_KEY => 'mega-account',
                    ],
                ]
            );

        return [[[[$eventData1], [$eventData2]]]];
    }

    /**
     * Get three EventData for two live event
     *
     * @return array
     */
    public function insertSocialInfoDataProvider(): array
    {
        $eventData1 = new EventData();
        $eventData1->setName('test event')
            ->setActive(1)
            ->setId(777)
            ->setAccounts(
                [
                    [
                        EventData::ACCOUNT_TYPE_KEY      => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                        EventData::ACCOUNT_USER_NAME_KEY => 'mega-account',
                    ],
                ]
            );

        $eventData2 = new EventData();
        $eventData2->setName('rewrite event')
            ->setActive(0)
            ->setId(777)
            ->setAccounts(
                [
                    [
                        EventData::ACCOUNT_TYPE_KEY      => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                        EventData::ACCOUNT_USER_NAME_KEY => 'mega-account',
                    ],
                ]
            );

        $eventData3 = new EventData();
        $eventData3->setName('new test event')
            ->setActive(1)
            ->setId(888)
            ->setAccounts(
                [
                    [
                        EventData::ACCOUNT_TYPE_KEY      => SocialType::SOCIAL_TYPE_NAME_TWITTER,
                        EventData::ACCOUNT_USER_NAME_KEY => 'mega-account',
                    ],
                ]
            );

        return [[[[1, $eventData1], [1, $eventData2], [2, $eventData3]]]];
    }
}
