<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 07.02.18
 * Time: 19:52
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Service;

use Doctrine\ORM\EntityManager;
use SocialTrackerBundle\Recording\Event\EventData;
use Symfony\Component\DependencyInjection\Container;

/**
 * Delete, update, insert social account
 */
class AccountManaging
{
    /** @var \SocialTrackerBundle\Repository\SocialAccount Social type repository */
    private $accountRepository;

    /** @var Container Symfony DI */
    private $container;

    /** @var EntityManager Doctrine Entity Manager */
    private $em;

    /** @var \SocialTrackerBundle\Repository\LiveEventAccounts Social type repository */
    private $eventAccountRepository;

    /** @var \SocialTrackerBundle\Repository\LiveEventPosts Social type repository */
    private $liveEventPostsRepository;

    /**
     * AccountManaging constructor.
     *
     * @param EntityManager $em        Doctrine Entity Manager
     * @param Container     $container Symfony DI
     */
    public function __construct(EntityManager $em, Container $container)
    {
        $this->em                       = $em;
        $this->container                = $container;
        $this->accountRepository        = $this->em->getRepository('SocialTrackerBundle:SocialAccount');
        $this->eventAccountRepository   = $this->em->getRepository('SocialTrackerBundle:LiveEventAccounts');
        $this->liveEventPostsRepository = $this->em->getRepository('SocialTrackerBundle:LiveEventPosts');
    }

    /**
     * Delete all event relations for live event
     *
     * @param int $originEventId Social live event
     */
    public function deleteAllEventRelations(int $originEventId)
    {
        /** Delete all LiveEventAccount relation for this live event */
        $this->eventAccountRepository->deleteAllRowsByOriginEventId($originEventId);

        /** Delete all LiveEventPost relation for this event */
        $this->liveEventPostsRepository->deleteAllRowsByOriginEventId($originEventId);
    }

    /**
     * Delete not used social account relation for live event
     *
     * @param int   $originEventId          Social live event
     * @param array $existingSocialAccounts Existing social accounts
     */
    public function deleteEventRelationExceptExistingAccount(int $originEventId, array $existingSocialAccounts)
    {
        /** Get used social account for live event */
        $usedAccountIds = $this->accountRepository->getAccountIdByOriginEventIdAndAccountInfo($originEventId, $existingSocialAccounts);

        /** Get not used social account for live event */
        $notUsedAccountIds = $this->accountRepository->getNotExistingAccountForEvent($originEventId, $usedAccountIds);

        /** Delete social account relation by account id and event id */
        if (count($notUsedAccountIds)) {

            /** Delete LiveEventAccount relation, if user account not exist for this live event */
            $this->eventAccountRepository->deleteRowByOriginEventIdAndAccountIds($originEventId, $notUsedAccountIds);

            /** Delete LiveEventPost relation, if user account not exist for this event */
            $this->liveEventPostsRepository->deleteRowByOriginEventIdAndAccountIds($originEventId, $notUsedAccountIds);
        }
    }

    /**
     * @param EventData $eventData Live event data
     */
    public function sendEventDataToQueue(EventData $eventData)
    {
        $eventDataService = $this->container->get('social_tracker.event_data.service');
        $SQSClient        = $this->container->get('social_tracker.aws.sqs.client');
        $SQSClient        = $SQSClient->getSQSClient();

        $jsonEventDataRows = $eventDataService->getJsonRowAccount($eventData);

        foreach ($jsonEventDataRows as $index => $eventDataRow) {
            try {
                /** Prepare parameters for queue */
                $params = [
                    'MessageBody' => (string) $eventDataRow,
                    'QueueUrl'    => $this->container->getParameter('account_validate_queue_url'),
                ];

                /** Send message to queue */
                $SQSClient->sendMessage($params);
            } catch (\Exception $e) {
            }
        }
    }
}
