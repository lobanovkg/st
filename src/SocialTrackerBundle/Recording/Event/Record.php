<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 05.01.18
 * Time: 23:20
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Recording\Event;

use Doctrine\ORM\EntityManager;
use SocialTrackerBundle\Entity\LiveEvent;
use SocialTrackerBundle\Entity\LiveEventAccounts;
use SocialTrackerBundle\Entity\SocialAccount;
use SocialTrackerBundle\Entity\SocialType;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class Record Writing live event data to DB
 */
class Record
{
    /** @var \SocialTrackerBundle\Repository\SocialAccount Social type repository */
    private $accountRepository;

    /** @var Container Symfony DI */
    private $container;

    /** @var EntityManager Doctrine Entity Manager */
    private $em;

    /** @var \SocialTrackerBundle\Repository\LiveEventAccounts Social type repository */
    private $eventAccountRepository;

    /** @var object|EventDataService Service for working with EventData object */
    private $eventDataService;

    /** @var \SocialTrackerBundle\Repository\LiveEvent Social type repository */
    private $eventRepository;

    /** @var \SocialTrackerBundle\Repository\SocialType Social type repository */
    private $socialTypeRepository;

    /**
     * Record constructor.
     *
     * @param EntityManager $em        Doctrine Entity Manager
     * @param Container     $container Symfony DI
     */
    public function __construct(EntityManager $em, Container $container)
    {
        $this->em                     = $em;
        $this->container              = $container;
        $this->eventDataService       = $this->container->get('social_tracker.event_data.service');
        $this->socialTypeRepository   = $this->em->getRepository('SocialTrackerBundle:SocialType');
        $this->accountRepository      = $this->em->getRepository('SocialTrackerBundle:SocialAccount');
        $this->eventRepository        = $this->em->getRepository('SocialTrackerBundle:LiveEvent');
        $this->eventAccountRepository = $this->em->getRepository('SocialTrackerBundle:LiveEventAccounts');
    }

    /**
     * Writing live event data
     *
     * @param EventData $eventData Live event data
     */
    public function record(EventData $eventData)
    {
        $this->insertUpdateLiveEvent($eventData);
        $this->insertSocialType($eventData);
        $this->insertSocialAccount($eventData);
        $this->createAccountAndLiveEventRelation($eventData);
        $this->em->clear();
    }

    /**
     * Create social account and live event relation
     *
     * @param EventData $eventData Live event data
     */
    private function createAccountAndLiveEventRelation(EventData $eventData)
    {
        $event = $this->eventRepository->findOneBy(['originId' => $eventData->getId()]);

        $socialUsers = $this->eventDataService->getSocialUserName($eventData);
        foreach ($socialUsers as $type => $users) {
            $typeEntity = $this->socialTypeRepository->findOneBy(['name' => $type]);
            foreach ($users as $user) {
                $userEntity = $this->accountRepository->findOneBy(['userName' => $user, 'socialType' => $typeEntity]);
                if (!empty($this->eventAccountRepository->findBy(['account' => $userEntity, 'liveEvent' => $event]))) {
                    continue;
                }
                /** Create live event relation of social account user */
                $liveEventAccountEntity = new LiveEventAccounts();
                $liveEventAccountEntity->setLiveEvent($event)->setAccount($userEntity);
                $this->em->persist($liveEventAccountEntity);
                $this->em->flush();
            }
        }
    }

    /**
     * Insert social account
     *
     * @param EventData $eventData Social event data
     */
    private function insertSocialAccount(EventData $eventData)
    {
        $socialUsers = $this->eventDataService->getSocialUserName($eventData);
        foreach ($socialUsers as $type => $users) {
            $typeEntity = $this->socialTypeRepository->findOneBy(['name' => $type]);
            foreach ($users as $user) {
                $userEntity = $this->accountRepository->findBy(['userName' => $user, 'socialType' => $typeEntity]);

                /** Continue if account user exist */
                if (!empty($userEntity)) {
                    continue;
                }
                /** Create new social account if it not exist */
                $account = new SocialAccount();
                $account->setSocialType($typeEntity)
                    ->setUserName($user);
                $this->em->persist($account);
                $this->em->flush();
            }
        }
    }

    /**
     * Insert social type
     *
     * @param EventData $eventData Social event data
     */
    private function insertSocialType(EventData $eventData)
    {
        /** Get social types from live event data */
        $socialTypes = $this->eventDataService->getSocialType($eventData);
        foreach ($socialTypes as $socialType) {
            $typeEntity = $this->socialTypeRepository->findOneBy(['name' => $socialType]);

            /** Create social type if not exist in db */
            if (is_null($typeEntity)) {
                $type = new SocialType();
                $type->setName($socialType);
                $this->em->persist($type);
                $this->em->flush();
            }
        }
    }

    /**
     * Insert/Update live event
     *
     * @param EventData $eventData Social event data
     */
    private function insertUpdateLiveEvent(EventData $eventData)
    {
        $eventEntity = $this->em->getRepository('SocialTrackerBundle:LiveEvent');

        $event    = $eventEntity->findOneBy(['originId' => $eventData->getId()]);
        $newEvent = new LiveEvent();

        /** If live event not exist, insert new live event */
        if (is_null($event)) {
            $newEvent->setName($eventData->getName())
                ->setOriginId($eventData->getId())
                ->setActive($eventData->getActive());
            $this->em->persist($newEvent);
            $this->em->flush();
        } else {
            /** If live event exist, update live event fields */
            $newEvent->setId($event->getId())
                ->setOriginId($eventData->getId())
                ->setName($eventData->getName())
                ->setActive($eventData->getActive());
            $this->em->merge($newEvent);
            $this->em->flush();
        }
    }
}
