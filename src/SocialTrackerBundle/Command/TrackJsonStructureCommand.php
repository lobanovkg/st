<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 16.03.18
 * Time: 17:15
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Command;

use Monolog\Logger;
use SocialTrackerBundle\Crawler\Instagram\Crawler;
use SocialTrackerBundle\Crawler\Instagram\PostCrawler;
use SocialTrackerBundle\Crawler\Instagram\Validate\ValidateCrawlerResponseStructure;
use SocialTrackerBundle\Logger\LoggerSettingsData;
use SocialTrackerBundle\Service\ScriptStatistics;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TrackJsonStructureCommand
 */
class TrackJsonStructureCommand extends ContainerAwareCommand
{
    const ACCOUNT_USER_NAME = 'account-name';
    const ENABLE_DEBUG_OPTION_NAME = 'enable-debug';
    const LOG_FILE_NAME = 'log-name';
    const POST_URL_NAME = 'post-url';

    /** @var string Instagram account user name for tracking json structure on instagram API */
    private $accountName;

    /** @var Crawler Instagram crawler */
    private $crawler;

    /** @var bool Enable debug mode */
    private $debug = false;

    /** @var Logger Symfony Monolog logger */
    private $logger;

    /** @var PostCrawler Instagram post crawler */
    private $postCrawler;

    /** @var string Instagram post url for tracking json structure on instagram API */
    private $postUrl;

    /** @var ValidateCrawlerResponseStructure Validator crawler response */
    private $validateCrawler;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('social_tracker:track_json_structure_command')
            ->addOption(
                self::ENABLE_DEBUG_OPTION_NAME,
                null,
                InputOption::VALUE_NONE,
                'Enable command debug.'
            )->addOption(
                self::ACCOUNT_USER_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Account url for tracking json structure on instagram API'
            )->addOption(
                self::POST_URL_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Post url for tracking json structure on instagram API'
            )->addOption(
                self::LOG_FILE_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Parameter log file name for Symfony container'
            )->setDescription('Track JSON structure on instagram API.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ScriptStatistics::start();

        $accountPostCollection = $this->crawler
            ->setAccounts([$this->accountName])
            ->grab();

        if (false === $this->validateCrawler->isValid($accountPostCollection)) {
            $this->logger->error($this->validateCrawler->getErrorMessage(), [$this->accountName]);
            $output->writeln($this->validateCrawler->getErrorMessage());
        }

        $postCollection = $this->postCrawler
            ->setPostUrl($this->postUrl)
            ->grab();

        if (false === $this->validateCrawler->isValid($postCollection)) {
            $this->logger->error($this->validateCrawler->getErrorMessage(), [$this->postUrl]);
            $output->writeln($this->validateCrawler->getErrorMessage());
        }

        ScriptStatistics::end($this->logger, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->crawler         = $this->getContainer()->get('social_tracker.instagram.account_crawler');
        $this->postCrawler     = $this->getContainer()->get('social_tracker.instagram.post_crawler');
        $this->validateCrawler = $this->getContainer()->get('social_tracker.instagram.validate_crawler');

        /** Set console options */
        $this->accountName = $input->getOption(self::ACCOUNT_USER_NAME);
        $this->postUrl     = $input->getOption(self::POST_URL_NAME);
        $this->debug       = $input->getOption(self::ENABLE_DEBUG_OPTION_NAME);

        /** Initialization logger */
        $this->initLogger($this->debug, $input->getOption(self::LOG_FILE_NAME));
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
        $loggerSettings->setChannelName('track_json_structure_command')
            ->setLogFileName($this->getContainer()->getParameter($logFileName))
            ->setEnable($enableDebug);

        $logWrapper   = $this->getContainer()->get('social_tracker.log_wrapper');
        $this->logger = $logWrapper->setLoggerSettingsData($loggerSettings)
            ->initStreamHandler()
            ->getLogger();
    }
}
