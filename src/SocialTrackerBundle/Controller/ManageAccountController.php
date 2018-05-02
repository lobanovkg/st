<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 09.01.18
 * Time: 13:33
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Controller;

use SocialTrackerBundle\Recording\Event\EventData;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ManageAccountController Update/Insert/Delete live event, accounts, tags
 */
class ManageAccountController extends Controller
{
    /**
     * Update/Insert/Delete live event, accounts
     *
     * @param Request $request HTTP request class
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateAction(Request $request)
    {
        /** Set EventData for record */
        $eventData = new EventData();
        $eventData->setId((int) $request->get('id'))
            ->setName($request->get('name'))
            ->setActive((int) $request->get('active'))
            ->setAccounts($request->get('accounts'));

        $accountValidate = $this->container->get('social_tracker.event.validator');

        $accountValidate->setData($eventData);

        /** If live event data invalid, return error message */
        if (true !== $accountValidate->validate()) {
            return $this->json(['Account Validation Error.']);
        }

        $eventDataService       = $this->container->get('social_tracker.event_data.service');
        $accountManagingService = $this->container->get('social_tracker.account_managing.service');
        $accountRecord          = $this->container->get('social_tracker.event.record');

        $eventDataService->setEventData($eventData);

        /** @var array $existingAccounts Existing accounts from event data */
        $existingAccounts = $eventDataService->getExistSocialAccounts();

        /** @var array $newAccounts New accounts from event data */
        $newAccounts = $eventDataService->getNewSocialAccounts();

        if (count($existingAccounts)) {
            /** Delete all Social Event Accounts relations for event, except existing accounts */
            $accountManagingService->deleteEventRelationExceptExistingAccount(
                $eventData->getId(),
                $existingAccounts
            );

            /** Get cloned EventData with existing accounts */
            $eventDataExistingAccounts = $eventDataService->getCloneEventData(
                $existingAccounts,
                $eventData
            );

            /** Recording EventData */
            $accountRecord->record($eventDataExistingAccounts);
        } else {
            /** Delete all event relation if not exist account ids for this event */
            $accountManagingService->deleteAllEventRelations($eventData->getId());
        }

        if (count($newAccounts)) {
            /** Get cloned EventData with new accounts */
            $eventDataNewAccounts = $eventDataService->getCloneEventData(
                $newAccounts,
                $eventData
            );

            /** Set EventData object with new accounts into AWS SQS */
            $accountManagingService->sendEventDataToQueue($eventDataNewAccounts);
        }

        return $this->json(['Success.']);
    }
}
