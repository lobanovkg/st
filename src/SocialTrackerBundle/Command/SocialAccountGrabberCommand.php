<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 04.01.18
 * Time: 19:57
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Command;

use Aws\Result;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use SocialTrackerBundle\AWS\SQS\SQSService;
use SocialTrackerBundle\Logger\LoggerSettingsData;
use SocialTrackerBundle\Recording\Post\PostRecord;
use SocialTrackerBundle\Repository\SocialAccount;
use SocialTrackerBundle\Repository\SocialType;
use SocialTrackerBundle\Service\ScriptStatistics;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class SocialAccountGrabberCommand Parse social post and write into db
 */
class SocialAccountGrabberCommand extends ContainerAwareCommand
{
    const ACCOUNTS_IN_QUEUE = 100;
    const ACCOUNT_ID_OPTION_NAME = 'account-id';
    const ENABLE_DAEMON_OPTION_NAME = 'enable-daemon';
    const ENABLE_DEBUG_OPTION_NAME = 'enable-debug';
    const QUEUE_LIMIT_OPTION_NAME = 'queue-limit';

    /** @var int Social account id */
    private $accountId;

    /** @var SocialAccount Repository */
    private $accountRepository;

    /** @var bool Enable daemon mode */
    private $daemon = false;

    /** @var bool Enable debug mode */
    private $debug = false;

    /** @var EntityManager Doctrine entity manager */
    private $em;

    /** @var Logger Handler for log writing */
    private $logger;

    /** @var SQSService AWS SQS Service */
    private $sqsService;

    /** @var int Queue limit */
    private $queueLimit;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('social_tracker:social_account_grabber_command')
            ->addOption(self::ENABLE_DAEMON_OPTION_NAME, null, InputOption::VALUE_NONE, 'Enable daemon mode.')
            ->addOption(self::ENABLE_DEBUG_OPTION_NAME, null, InputOption::VALUE_NONE, 'Enable command debug.')
            ->addOption(self::ACCOUNT_ID_OPTION_NAME, null, InputOption::VALUE_OPTIONAL, 'Account id.')
            ->addOption(
                self::QUEUE_LIMIT_OPTION_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Accounts limit in queue.',
                self::ACCOUNTS_IN_QUEUE
            )
            ->setDescription('Parse social post from twitter and instagram!');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (false === $this->daemon) {
            ScriptStatistics::start();
        }

        /** Lock multiply calling command */
        $lockHandler = new LockHandler('social-crawler-command.lock');
        if (!$lockHandler->lock()) {
            $this->logger->info('the resource "social-crawler-command" is already locked by another process!');

            return;
        }

        /** If isset command option accountId start worker for this account */
        if ($this->accountId) {
            $this->startAccountGrabber($this->accountId);
        } else {

            /** Needed for counting queue limit */
            $iterator = 0;

            while (true) {
                /** If all parts of condition false -- enable daemon mode */
                if (false === $this->daemon && $iterator >= $this->queueLimit) {
                    break;
                }

                /** SQS message processing */
                $this->processMessage();

                /** Counting iteration for queue limit, only if not daemon mode */
                if (false === $this->daemon) {
                    ++$iterator;
                }
            }
        }

        if (false === $this->daemon) {
            ScriptStatistics::end($this->logger, $output);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** Set console command options into variables */
        $this->daemon     = $input->getOption(self::ENABLE_DAEMON_OPTION_NAME);
        $this->debug      = (bool) $input->getOption(self::ENABLE_DEBUG_OPTION_NAME);
        $this->accountId  = (int) $input->getOption(self::ACCOUNT_ID_OPTION_NAME);
        $this->queueLimit = (int) $input->getOption(self::QUEUE_LIMIT_OPTION_NAME);

        $this->accountRepository = $this->em->getRepository('SocialTrackerBundle:SocialAccount');

        /** Set SQS Service for managing queue */
        $this->sqsService = $this->getContainer()->get('social_tracker.aws.sqs.service');

        /** Initialization logger */
        $this->initLogger($this->debug);
    }

    /**
     * Initialization logger
     *
     * @param bool $enableDebug Enable debug
     */
    private function initLogger(bool $enableDebug)
    {
        /** Set Logger settings data */
        $loggerSettings = new LoggerSettingsData();
        $loggerSettings->setChannelName('social-crawler-command')
            ->setLogFileName($this->getContainer()->getParameter('social_crawler_command_log_path'))
            ->setEnable($enableDebug);

        $logWrapper   = $this->getContainer()->get('social_tracker.log_wrapper');
        $this->logger = $logWrapper->setLoggerSettingsData($loggerSettings)
            ->initStreamHandler()
            ->getLogger();
    }

    /**
     * SQS message processing
     */
    private function processMessage()
    {
        $receiveQueueUrl = $this->getContainer()->getParameter('account_grabber_queue_url');

        /** @var Result $result Response from AWS SQS */
        $result = $this->sqsService->receiveMessage($receiveQueueUrl);

        /** Validate response format */
        if (!$result->hasKey('Messages')) {
            return;
        }

        /** Cycle of queue messages */
        foreach ($result->get('Messages') as $message) {
            try {
                /** $message['Body'] stores account id */
                $this->startAccountGrabber((int) $message['Body']);
            } catch (\Exception $e) {
                $this->logger->warning('Social Crawler fetch message error! '.$e->getMessage(), [$message['Body']]);
                continue;
            }
            $this->sqsService->deleteMessage($message['ReceiptHandle'], $receiveQueueUrl);
        }
    }

    /**
     * Run Instagram grabber
     *
     * @param array $accounts Social accounts
     *
     * @return array
     */
    private function runInstagramCrawler(array $accounts): array
    {
        $grabber = $this->getContainer()->get('social_tracker.instagram.account_crawler');

        $grabber->setDebug($this->debug)
            ->setAccounts($accounts);

        return $grabber->grab();
    }

    /**
     * Run Twitter grabber
     *
     * @param array $accounts Social accounts
     *
     * @return array
     */
    private function runTwitterCrawler(array $accounts): array
    {
        $grabber = $this->getContainer()->get('social_tracker.twitter.account_crawler');

        $grabber->setDebug($this->debug)
            ->setAccounts($accounts);

        return $grabber->grab();
    }

    /**
     * Start social post grabber
     *
     * @param int $accountId Social account id
     */
    private function startAccountGrabber(int $accountId)
    {
        $data = [];

        /** @var \SocialTrackerBundle\Entity\SocialAccount $accountEntity Social account entity */
        $accountEntity = $this->accountRepository->find($accountId);

        /** @var \SocialTrackerBundle\Entity\SocialType $socialTypeEntity Social type entity */
        $socialTypeEntity = $accountEntity->getSocialType();

        $eventAccountRepository = $this->em->getRepository('SocialTrackerBundle:LiveEventAccounts');

        if (!$eventAccountRepository->hasActiveEvents($accountId)) {
            /**
             * Return if account id not have active event
             */
            return;
        }

        /** Set accounts for Instagram grabber */
        if ($socialTypeEntity->getName() === SocialType::SOCIAL_TYPE_NAME_INSTAGRAM) {
            $this->logger->debug('Instagram accounts', [$accountEntity->getUserName()]);

            /** Get grabbed data */
            $data = $this->runInstagramCrawler([$accountId => $accountEntity->getUserName()]);

            /** Set accounts for Twitter grabber */
        } elseif ($socialTypeEntity->getName() === SocialType::SOCIAL_TYPE_NAME_TWITTER) {
            $this->logger->debug('Twitter accounts', [$accountEntity->getUserName()]);

            /** Get grabbed data */
            $data = $this->runTwitterCrawler([$accountId => $accountEntity->getUserName()]);
        }

        /** @var PostRecord $recorder Post recording service */
        $recorder = $this->getContainer()->get('social_tracker.post.record');

        try {
            /** Inserting grabbed data into db */
            $recorder->record($data);
        } catch (\Throwable $t) {
            $this->logger->error($t->getMessage(), [$accountId]);
        }
    }
}
