<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 04.01.18
 * Time: 19:57
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Command;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use SocialTrackerBundle\AWS\SQS\SQSService;
use SocialTrackerBundle\Logger\LoggerSettingsData;
use SocialTrackerBundle\Repository\LiveEventAccounts;
use SocialTrackerBundle\Service\ScriptStatistics;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SocialAccountToSQSCommand adding social account to AWS SQS
 */
class SocialAccountToSQSCommand extends ContainerAwareCommand
{
    const ENABLE_DEBUG_OPTION_NAME = 'enable-debug';
    const LOG_FILE_NAME = 'log-name';
    const QUEUE_URL = 'queue-url';

    /** @var EntityManager Doctrine entity manager */
    private $em;

    /** @var LiveEventAccounts Live event account repository */
    private $liveAccountsRepository;

    /** @var Logger Handler for log writing */
    private $logger;

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
            ->setName('social_tracker:social_account_to_sqs_command')
            ->addOption(self::ENABLE_DEBUG_OPTION_NAME, null, InputOption::VALUE_NONE, 'Enable command debug.')
            ->addOption(
                self::LOG_FILE_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Log file name for Symfony container'
            )->addOption(
                self::QUEUE_URL,
                null,
                InputOption::VALUE_REQUIRED,
                'Parameter AWS SQS queue url for Symfony container'
            )
            ->setDescription('Add social account to sqs');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ScriptStatistics::start();

        /** Get accounts for active live event */
        $accountIds = $this->liveAccountsRepository->getActiveAccountIds();

        /** Send message to queue */
        foreach ($accountIds as $accountId) {
            try {
                $this->logger->info('Send accountId to queue', [$accountId]);
                $this->sqsService->sendMessage($accountId, $this->queueUrl);
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage(), [$accountId]);
                $output->writeln('Exception for accountId - '.$accountId);
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
        $this->em                     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->liveAccountsRepository = $this->em->getRepository('SocialTrackerBundle:LiveEventAccounts');
        $this->sqsService             = $this->getContainer()->get('social_tracker.aws.sqs.service');

        /** Set command options */
        $aliasQueueUrl = $input->getOption(self::QUEUE_URL);

        /** @var string queueUrl Set full queue */
        $this->queueUrl = $this->getContainer()->getParameter($aliasQueueUrl);

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
        $loggerSettings->setChannelName('command-social-account-to-sqs')
            ->setLogFileName($this->getContainer()->getParameter($logFileName))
            ->setEnable($enableDebug);

        $logWrapper   = $this->getContainer()->get('social_tracker.log_wrapper');
        $this->logger = $logWrapper->setLoggerSettingsData($loggerSettings)
            ->initStreamHandler()
            ->getLogger();
    }
}
