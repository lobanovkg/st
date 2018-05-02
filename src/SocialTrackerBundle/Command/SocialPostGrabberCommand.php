<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 12.02.18
 * Time: 15:57
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Command;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use SocialTrackerBundle\AWS\SQS\SQSService;
use SocialTrackerBundle\Crawler\Instagram\PostCrawler;
use SocialTrackerBundle\Logger\LoggerSettingsData;
use SocialTrackerBundle\Recording\Post\PostRecord;
use SocialTrackerBundle\Service\ScriptStatistics;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Social post grabber
 */
class SocialPostGrabberCommand extends ContainerAwareCommand
{
    const ENABLE_DAEMON_OPTION_NAME = 'enable-daemon';
    const ENABLE_DEBUG_OPTION_NAME = 'enable-debug';
    const POSTS_IN_QUEUE = 100;
    const QUEUE_LIMIT_OPTION_NAME = 'queue-limit';
    const QUEUE_URL = 'queue-url';

    /** @var bool Enable daemon mode */
    private $daemon = false;

    /** @var EntityManager Doctrine entity manager */
    private $em;

    /** @var Logger Handler for writing log to file */
    private $logger;

    /** @var PostCrawler Social post grabber */
    private $postCrawler;

    /** @var int Worker queue limit */
    private $queueLimit;

    /** @var string AWS SQS queue url */
    private $queueUrl;

    /** @var PostRecord $recorder Post recording service */
    private $recorder;

    /** @var SQSService AWS SQS Service */
    private $sqsService;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('social_tracker:social_post_grabber_command')
            ->addOption(self::ENABLE_DAEMON_OPTION_NAME, null, InputOption::VALUE_NONE, 'Enable daemon mode.')
            ->addOption(
                self::QUEUE_LIMIT_OPTION_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Accounts limit in queue.',
                self::POSTS_IN_QUEUE
            )->addOption(
                self::QUEUE_URL,
                null,
                InputOption::VALUE_REQUIRED,
                'Parameter AWS SQS queue url for Symfony container'
            )
            ->addOption(self::ENABLE_DEBUG_OPTION_NAME, null, InputOption::VALUE_NONE, 'Enable command debug.')
            ->setDescription('Social post grabber.');
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
        $lockHandler = new LockHandler($this->queueUrl.'-social-post-grabber-command.lock');
        if (!$lockHandler->lock()) {
            $this->logger->info('the resource "social-post-grabber-command" for queue url "'.$this->queueUrl.'" is already locked by another process!');

            return;
        }

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

        if (false === $this->daemon) {
            ScriptStatistics::end($this->logger, $output);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager Doctrine entity manager */
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** Set command options */
        $this->daemon     = $input->getOption(self::ENABLE_DAEMON_OPTION_NAME);
        $this->queueLimit = (int) $input->getOption(self::QUEUE_LIMIT_OPTION_NAME);
        $aliasQueueUrl    = $input->getOption(self::QUEUE_URL);

        /** @var string queueUrl Set full queue */
        $this->queueUrl = $this->getContainer()->getParameter($aliasQueueUrl);

        /** @var SQSService SQS service */
        $this->sqsService = $this->getContainer()->get('social_tracker.aws.sqs.service');

        /** @var PostCrawler postCrawler Post crawler service */
        $this->postCrawler = $this->getContainer()->get('social_tracker.instagram.post_crawler');

        /** @var PostRecord $recorder Post recording service */
        $this->recorder = $this->getContainer()->get('social_tracker.post.record');

        /** Initialization logger */
        $this->initLogger((bool) $input->getOption(self::ENABLE_DEBUG_OPTION_NAME));
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
        $loggerSettings->setChannelName('social-post-grabber')
            ->setLogFileName($this->getContainer()->getParameter('instagram_post_grabber_log_path'))
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
        /** Get response from AWS SQS */
        $result = $this->sqsService->receiveMessage($this->queueUrl);

        /** Validate response format */
        if (!$result->hasKey('Messages')) {
            return;
        }

        /** Cycle of queue messages */
        foreach ($result->get('Messages') as $message) {
            try {
                /** $message['Body'] stores post id */
                $data = $this->postCrawler->setPostId((int) $message['Body'])->grab();

                try {
                    /** Recording parsed data into DB */
                    $this->recorder->record($data);
                } catch (\Throwable $t) {
                    $this->logger->error($t->getMessage(), [$message['Body']]);
                }

                /** Deleting message from AWS SQS */
                $this->sqsService->deleteMessage($message['ReceiptHandle'], $this->queueUrl);
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage(), [$message['Body']]);
                continue;
            }
        }
    }
}
