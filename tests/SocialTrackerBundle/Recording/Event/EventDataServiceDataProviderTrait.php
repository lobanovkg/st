<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 01.03.18
 * Time: 0:38
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\Recording\Event;

use SocialTrackerBundle\Recording\Event\EventData;
use SocialTrackerBundle\Repository\SocialType;

/**
 * Class EventDataServiceDataProviderTrait
 */
trait EventDataServiceDataProviderTrait
{
    /**
     * Get accounts and event data
     *
     * @return array
     */
    public function getAccountsAndEventData(): array
    {
        $eventData = new EventData();
        $eventData->setName('Test event')
            ->setActive(1)
            ->setId(777)
            ->setAccounts(
                [
                    [
                        EventData::ACCOUNT_TYPE_KEY      => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                        EventData::ACCOUNT_USER_NAME_KEY => 'mega-account',
                    ],
                    [
                        EventData::ACCOUNT_TYPE_KEY      => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                        EventData::ACCOUNT_USER_NAME_KEY => 'bla',
                    ],
                ]
            );

        $accounts = ['in' => ['instagram'], 'tw' => ['twitter'], 'fb' => ['facebook']];

        return [[$accounts, $eventData]];
    }

    /**
     * Get valid event data with exist social accounts
     *
     * @return array
     */
    public function getExistEventData(): array
    {
        $eventData = new EventData();
        $eventData->setName('Test event')
            ->setActive(1)
            ->setId(777)
            ->setAccounts(
                [
                    [
                        EventData::ACCOUNT_TYPE_KEY      => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                        EventData::ACCOUNT_USER_NAME_KEY => 'test-instagram',
                    ],
                ]
            );

        return [[$eventData]];
    }

    /**
     * Get valid event data with new and exist social accounts
     *
     * @return array
     */
    public function getNewAndExistEventData(): array
    {
        $eventData = new EventData();
        $eventData->setName('Test event')
            ->setActive(1)
            ->setId(777)
            ->setAccounts(
                [
                    [
                        EventData::ACCOUNT_TYPE_KEY      => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                        EventData::ACCOUNT_USER_NAME_KEY => 'test-instagram-account',
                    ],
                    [
                        EventData::ACCOUNT_TYPE_KEY      => SocialType::SOCIAL_TYPE_NAME_INSTAGRAM,
                        EventData::ACCOUNT_USER_NAME_KEY => 'test-instagram',
                    ],
                ]
            );

        return [[$eventData]];
    }

    /**
     * Get valid event data with new social accounts
     *
     * @return array
     */
    public function getNewEventData(): array
    {
        $eventData = new EventData();
        $eventData->setName('Test event')
            ->setActive(1)
            ->setId(777)
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
