<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 09.01.18
 * Time: 13:33
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Command;

use Monolog\Logger;
use SocialTrackerBundle\AWS\SQS\SQSService;
use SocialTrackerBundle\Logger\LoggerSettingsData;
use SocialTrackerBundle\Recording\Event\EventData;
use SocialTrackerBundle\Recording\Event\Record;
use SocialTrackerBundle\Recording\Event\Validator\Validate;
use SocialTrackerBundle\Service\ScriptStatistics;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Validate social account
 */
class ValidateSocialAccountCommand extends ContainerAwareCommand
{
    const ACCOUNTS_IN_QUEUE = 100;
    const ENABLE_DAEMON_OPTION_NAME = 'enable-daemon';
    const ENABLE_DEBUG_OPTION_NAME = 'enable-debug';
    const QUEUE_LIMIT_OPTION_NAME = 'queue-limit';

    /** @var bool Enable daemon mode */
    private $daemon = false;

    /** @var Validate Validator for EventData */
    private $eventDataValidate;

    /** Writing live event data to DB */
    private $eventRecord;

    /** @var Logger Handler for log writing */
    private $logger;

    /** @var int Queue limit */
    private $queueLimit;

    /** @var SQSService AWS SQS Service */
    private $sqsService;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('social_tracker:validate_social_account_command')
            ->addOption(self::ENABLE_DAEMON_OPTION_NAME, null, InputOption::VALUE_NONE, 'Enable daemon mode.')
            ->addOption(self::ENABLE_DEBUG_OPTION_NAME, null, InputOption::VALUE_NONE, 'Enable command debug.')
            ->addOption(
                self::QUEUE_LIMIT_OPTION_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Accounts limit in queue.',
                self::ACCOUNTS_IN_QUEUE
            )
            ->setDescription('Validate social account.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ScriptStatistics::start();

        /** Lock multiply calling command */
        $lockHandler = new LockHandler('validate-social-account-command.lock');
        if (!$lockHandler->lock()) {
            $this->logger->info('the resource "validate-social-account-command" is already locked by another process!');

            return;
        }

        /** Needed for counting queue limit */
        $iterator = 1;

        while (true) {

            /** If all parts of condition false -- enable daemon mode */
            if (false === $this->daemon && $iterator > $this->queueLimit) {
                break;
            }

            /** SQS message processing */
            $this->processMessage();

            /** Counting iteration for queue limit, only if not daemon mode */
            if (false === $this->daemon) {
                ++$iterator;
            }
        }

        ScriptStatistics::end($this->logger, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        /** Set console command options */
        $this->daemon     = $input->getOption(self::ENABLE_DAEMON_OPTION_NAME);
        $this->queueLimit = (int) $input->getOption(self::QUEUE_LIMIT_OPTION_NAME);

        /** @var SQSService AWS SQS Service */
        $this->sqsService = $this->getContainer()->get('social_tracker.aws.sqs.service');

        /** @var Validate accountValidate Validate social accounts */
        $this->eventDataValidate = $this->getContainer()->get('social_tracker.event.validator');

        /** @var Record Writing live event data to DB */
        $this->eventRecord = $this->getContainer()->get('social_tracker.event.record');

        /** Initialization logger */
        $this->initLogger((bool) $input->getOption(self::ENABLE_DEBUG_OPTION_NAME));
    }

    /**
     * Deserialize EventData from json format into object
     *
     * @param string $jsonEventData EventData into json format
     *
     * @return EventData
     */
    private function deserializeQueueMessage(string $jsonEventData): EventData
    {
        $serialize = new Serializer(
            [new GetSetMethodNormalizer(), new ArrayDenormalizer()],
            [new JsonEncoder()]
        );

        return $serialize->deserialize($jsonEventData, EventData::class, 'json');
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
        $loggerSettings->setChannelName('validate-account-command')
            ->setLogFileName($this->getContainer()->getParameter('validate_account_log_path'))
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
        $sqsUrl = $this->getContainer()->getParameter('account_validate_queue_url');

        /** Get response from AWS SQS */
        $result = $this->sqsService->receiveMessage($sqsUrl);

        /** Validate response format */
        if (!$result->hasKey('Messages')) {
            return;
        }

        /** Cycle of queue messages */
        foreach ($result->get('Messages') as $message) {
            try {

                /** $message['Body'] stores EventData of json format */
                $eventData = $this->deserializeQueueMessage($message['Body']);

                /** Initialize EventData validator */
                $this->eventDataValidate->setData($eventData)
                    ->setTwitterValidator($this->getContainer()->get('social_tracker.twitter.account_validator'))
                    ->setInstagramValidator($this->getContainer()->get('social_tracker.instagram.account_validator'));

                /** If live event data invalid, return error message */
                $isValid = $this->eventDataValidate->validate();
                if (true === $isValid) {

                    /** Writing EventData to DB */
                    $this->eventRecord->record($eventData);
                } else {

                    /** Write warning message if invalid EventData */
                    $this->logger->warning($isValid, [$message['Body']]);
                }
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage(), [$message['Body']]);
                continue;
            }

            /** Deleting message from AWS SQS */
            $this->sqsService->deleteMessage($message['ReceiptHandle'], $sqsUrl);
        }
    }
}
