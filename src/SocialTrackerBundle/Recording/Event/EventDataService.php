<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 07.02.18
 * Time: 23:02
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Recording\Event;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Event data service
 */
class EventDataService
{
    /** @var \SocialTrackerBundle\Repository\SocialAccount Social type repository */
    private $accountRepository;

    /** @var EntityManager Doctrine entity manager */
    private $em;

    /** @var EventData Social event data */
    private $eventData;

    /** @var array Existing social account from EventData */
    private $existSocialAccount = [];

    /** @var \SocialTrackerBundle\Repository\LiveEvent Live event repository */
    private $liveEventRepository;

    /** @var \SocialTrackerBundle\Repository\LiveEventAccounts Live event accounts repository */
    private $liveEventAccountRepository;

    /** @var array New social account from EventData */
    private $newSocialAccount = [];

    /** @var \SocialTrackerBundle\Repository\SocialType Social type repository */
    private $socialTypeRepository;

    /**
     * EventDataService constructor.
     *
     * @param EntityManager $em Doctrine entity manager
     */
    public function __construct(EntityManager $em)
    {
        $this->em                         = $em;
        $this->socialTypeRepository       = $this->em->getRepository('SocialTrackerBundle:SocialType');
        $this->accountRepository          = $this->em->getRepository('SocialTrackerBundle:SocialAccount');
        $this->liveEventRepository        = $this->em->getRepository('SocialTrackerBundle:LiveEvent');
        $this->liveEventAccountRepository = $this->em->getRepository('SocialTrackerBundle:LiveEventAccounts');
    }

    /**
     * Clone EventData with new accounts
     *
     * @param array     $accounts  Social accounts
     * @param EventData $eventData Live event data
     *
     * @return EventData
     */
    public function getCloneEventData(array $accounts, EventData $eventData): EventData
    {
        $newEventData = clone $eventData;

        $eventDataAccounts = [];
        foreach ($accounts as $socialType => $socialAccount) {
            foreach ($socialAccount as $account) {
                $eventDataAccounts[] = [EventData::ACCOUNT_TYPE_KEY => $socialType, EventData::ACCOUNT_USER_NAME_KEY => $account];
            }
        }
        $newEventData->setAccounts($eventDataAccounts);

        return $newEventData;
    }

    /**
     * Return exist social account
     * Returning format
     * array(
     *     socialTypeName_1 => array(socialAccountName_5, socialAccountName_6, ...),
     *     socialTypeName_3 => array(socialAccountName_7, socialAccountName_8, ...)
     * )
     *
     * @return array
     */
    public function getExistSocialAccounts()
    {
        $this->setNewAndExistingSocialAccount($this->eventData);

        return $this->existSocialAccount;
    }

    /**
     * Set social event data
     *
     * @param EventData $eventData Social event data
     */
    public function setEventData(EventData $eventData)
    {
        $this->eventData = $eventData;
    }

    /**
     * @param EventData $eventData
     *
     * @return array
     */
    public function getJsonRowAccount(EventData $eventData)
    {
        $serialize = new Serializer(
            [new GetSetMethodNormalizer(), new ArrayDenormalizer()],
            [new JsonEncoder()]
        );

        $result       = [];
        $newEventData = new EventData();
        $newEventData->setId($eventData->getId())->setName($eventData->getName())->setActive($eventData->getActive());
        foreach ($eventData->getAccounts() as $row) {
            $newEventData->setAccounts([$row]);
            $result[] = $serialize->serialize($newEventData, 'json');
        }

        return $result;
    }

    /**
     * Return new social account
     * Returning format
     * array(
     *     socialTypeName_1 => array(socialAccountName_1, socialAccountName_2, ...),
     *     socialTypeName_2 => array(socialAccountName_3, socialAccountName_4, ...)
     * )
     *
     * @return array
     */
    public function getNewSocialAccounts()
    {
        $this->setNewAndExistingSocialAccount($this->eventData);

        return $this->newSocialAccount;
    }

    /**
     * Get social type from live event data
     *
     * @param EventData $eventData Live event data
     *
     * @return array
     */
    public function getSocialType(EventData $eventData): array
    {
        $result = [];
        foreach ($eventData->getAccounts() as $account) {
            $result[] = $account[EventData::ACCOUNT_TYPE_KEY];
        }

        return array_unique($result);
    }

    /**
     * Get social user name from live event data, group by social type
     *
     * @param EventData $eventData Live event data
     *
     * @return array
     */
    public function getSocialUserName(EventData $eventData): array
    {
        $result = [];
        foreach ($eventData->getAccounts() as $account) {
            $result[$account[EventData::ACCOUNT_TYPE_KEY]][] = $account[EventData::ACCOUNT_USER_NAME_KEY];
        }

        return $result;
    }

    /**
     * Set new and existing social account from event data
     *
     * @param EventData $eventData Social event data
     */
    private function setNewAndExistingSocialAccount(EventData $eventData)
    {
        if (!empty($this->newSocialAccount) || !empty($this->existSocialAccount)) {
            return;
        }

        $socialUsers = $this->getSocialUserName($eventData);

        $liveEventEntity = $this->liveEventRepository->findBy(['originId' => $eventData->getId()]);

        foreach ($socialUsers as $type => $users) {
            $typeEntity = $this->socialTypeRepository->findOneBy(['name' => $type]);
            $users      = array_unique($users);
            foreach ($users as $user) {

                /** If new social type, add user and social type for resulting array */
                if (empty($typeEntity)) {
                    $this->newSocialAccount[$type][] = $user;
                    continue;
                }

                $userEntity = $this->accountRepository->findBy(['userName' => $user, 'socialType' => $typeEntity]);

                /** Get live event account relation entity */
                $liveEventRelationEntity = $this->liveEventAccountRepository->findBy(['liveEvent' => $liveEventEntity, 'account' => $userEntity]);

                /** If new social account, add user and social type for resulting array */
                if (empty($liveEventRelationEntity)) {
                    $this->newSocialAccount[$type][] = $user;
                    continue;
                }

                /** Add existing social user and social type for resulting array */
                $this->existSocialAccount[$type][] = $user;
            }
        }
    }
}
