<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 20.01.18
 * Time: 21:01
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Command;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use SocialTrackerBundle\AWS\SQS\SQSService;
use SocialTrackerBundle\Logger\LoggerSettingsData;
use SocialTrackerBundle\Repository\SocialPost;
use SocialTrackerBundle\Service\ScriptStatistics;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SocialPostToSQSCommand - put posts url to AWS SQS
 */
class SocialPostToSQSCommand extends ContainerAwareCommand
{
    const ENABLE_DEBUG_OPTION_NAME = 'enable-debug';
    const FROM_MINUTES_OPTION_NAME = 'from';
    const LOG_FILE_NAME = 'log-name';
    const QUEUE_URL = 'queue-url';
    const TO_MINUTES_OPTION_NAME = 'to';

    /** Filter posts which were published at least {N} minutes ago from now */
    private $dateFrom;

    /** Filter posts which were published at most {N} minutes ago from now */
    private $dateTo;

    /** @var EntityManager Doctrine entity manager */
    private $em;

    /** @var Logger Symfony Monolog logger */
    private $logger;

    /** @var SocialPost Social post repository */
    private $postRepository;

    /** @var string AWS SQS queue url */
    private $queueUrl;

    /** @var SQSService AWS SQS Service */
    private $sqsService;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('social_tracker:social_post_to_sqs_command')
            ->addOption(self::ENABLE_DEBUG_OPTION_NAME, null, InputOption::VALUE_NONE, 'Enable command debug.')
            ->addOption(
                self::FROM_MINUTES_OPTION_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Filter posts which were published at least {N} minutes ago from now'
            )->addOption(
                self::TO_MINUTES_OPTION_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Filter posts which were published at most {N} minutes ago from now'
            )->addOption(
                self::LOG_FILE_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Parameter log file name for Symfony container'
            )->addOption(
                self::QUEUE_URL,
                null,
                InputOption::VALUE_REQUIRED,
                'Parameter AWS SQS queue url for Symfony container'
            )->setDescription('Add social post to sqs for validate.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ScriptStatistics::start();

        /** Convert date */
        $dateTo   = (string) date('Y-m-d H:i:s', strtotime('-'.$this->dateTo.' minutes', time()));
        $dateFrom = (string) date('Y-m-d H:i:s', strtotime('-'.$this->dateFrom.' minutes', time()));

        /** Get postIds by date range */
        $postIds = $this->postRepository->getPostIdsByDateRange($dateFrom, $dateTo);

        /** Send post to queue */
        foreach ($postIds as $postId) {
            try {

                /** Send postId to AWS SQS */
                $this->sqsService->sendMessage($postId, $this->getContainer()->getParameter($this->queueUrl));
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage(), [$postId]);
                $output->writeln('Exception for postId - '.$postId);
                $output->writeln($e->getMessage());
            }
        }

        ScriptStatistics::end($this->logger, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager Doctrine entity manager */
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var SocialPost Social post repository */
        $this->postRepository = $this->em->getRepository('SocialTrackerBundle:SocialPost');

        /** @var SQSService AWS SQS Service */
        $this->sqsService = $this->getContainer()->get('social_tracker.aws.sqs.service');

        /** Set console options */
        $this->dateFrom = $input->getOption(self::FROM_MINUTES_OPTION_NAME);
        $this->queueUrl = $input->getOption(self::QUEUE_URL);
        $this->dateTo   = $input->getOption(self::TO_MINUTES_OPTION_NAME);

        /** Initialization logger */
        $this->initLogger((bool) $input->getOption(self::ENABLE_DEBUG_OPTION_NAME), $input->getOption(self::LOG_FILE_NAME));
    }

    /**
     * Initialization logger
     *
     * @param bool   $enableDebug Enable debug
     * @param string $logFileName Log file name
     */
    private function initLogger(bool $enableDebug, string $logFileName)
    {
        /** Set Logger settings data */
        $loggerSettings = new LoggerSettingsData();
        $loggerSettings->setChannelName('social_posts_to_sqs_command')
            ->setLogFileName($this->getContainer()->getParameter($logFileName))
            ->setEnable($enableDebug);

        $logWrapper   = $this->getContainer()->get('social_tracker.log_wrapper');
        $this->logger = $logWrapper->setLoggerSettingsData($loggerSettings)
            ->initStreamHandler()
            ->getLogger();
    }
}
