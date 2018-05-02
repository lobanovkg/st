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
use SocialTrackerBundle\Crawler\Instagram\Crawler;
use SocialTrackerBundle\Helper\HttpHelper;
use SocialTrackerBundle\Logger\LoggerSettingsData;
use SocialTrackerBundle\Repository\SocialPost;
use SocialTrackerBundle\Repository\SocialType;
use SocialTrackerBundle\AWS\S3\S3Service;
use SocialTrackerBundle\Service\ScriptStatistics;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class ValidateSocialPostCommand
 */
class ValidateSocialPostCommand extends ContainerAwareCommand
{
    const ENABLE_DAEMON_OPTION_NAME = 'enable-daemon';
    const ENABLE_DEBUG_OPTION_NAME = 'enable-debug';
    const POSTS_IN_QUEUE = 100;
    const QUEUE_LIMIT_OPTION_NAME = 'queue-limit';

    /** @var bool Enable daemon mode */
    private $daemon = false;

    /** @var EntityManager Doctrine entity manager */
    private $em;

    /** @var Logger Handler for writing log to file */
    private $logger;

    /** @var int Worker queue limit */
    private $queueLimit;

    /** @var SQSService AWS SQS Service */
    private $sqsService;

    /** @var SocialPost Social post entity repository */
    private $socialPostRepository;

    /** @var S3Service AWS S3 upload image service */
    private $uploadImageService;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('social_tracker:validate_social_post_command')
            ->addOption(self::ENABLE_DAEMON_OPTION_NAME, null, InputOption::VALUE_NONE, 'Enable daemon mode.')
            ->addOption(
                self::QUEUE_LIMIT_OPTION_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Accounts limit in queue.',
                self::POSTS_IN_QUEUE
            )
            ->addOption(self::ENABLE_DEBUG_OPTION_NAME, null, InputOption::VALUE_NONE, 'Enable command debug.')
            ->setDescription('Validate social post on server code');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ScriptStatistics::start();

        /** Get Symfony component */
        $lockHandler = new LockHandler('validate-url.lock');

        /** Lock multiply calling command */
        if (!$lockHandler->lock()) {
            $output->writeln('INFO: the resource "validate-url" is already locked by another process!');
            $this->logger->info('the resource "validate-url" is already locked by another process!');

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
            $this->processMessage($output);

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
        /** @var EntityManager Doctrine entity manager */
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var SocialPost Social post entity repository */
        $this->socialPostRepository = $this->em->getRepository('SocialTrackerBundle:SocialPost');

        /** Set command options */
        $this->daemon     = $input->getOption(self::ENABLE_DAEMON_OPTION_NAME);
        $this->queueLimit = (int) $input->getOption(self::QUEUE_LIMIT_OPTION_NAME);

        /** @var SQSService AWS SQS Service */
        $this->sqsService = $this->getContainer()->get('social_tracker.aws.sqs.service');

        /** Set S3Service needed for checking and deleting image from AWS S3 */
        $this->uploadImageService = $this->getContainer()->get('social_tracker.aws.s3.service');

        /** Initialization logger */
        $this->initLogger((bool) $input->getOption(self::ENABLE_DEBUG_OPTION_NAME));
    }

    /**
     * @param string $postInfo
     *
     * @return string
     */
    private function getInstagramImageFromPostInfo(string $postInfo)
    {
        $postInfo = json_decode($postInfo, true);

        if (!is_array($postInfo) || !isset($postInfo[Crawler::THUMBNAIL_NAME]) || empty($postInfo[Crawler::THUMBNAIL_NAME])) {
            return '';
        }

        return $postInfo[Crawler::THUMBNAIL_NAME];
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
        $loggerSettings->setChannelName('validate-url')
            ->setLogFileName($this->getContainer()->getParameter('validate_post_log_path'))
            ->setEnable($enableDebug);

        $logWrapper   = $this->getContainer()->get('social_tracker.log_wrapper');
        $this->logger = $logWrapper->setLoggerSettingsData($loggerSettings)
            ->initStreamHandler()
            ->getLogger();
    }

    /**
     * SQS message processing
     *
     * @param OutputInterface $output
     */
    private function processMessage(OutputInterface $output)
    {
        $queueUrl = $this->getContainer()->getParameter('validate_post_url_queue_url');

        /** Get response from AWS SQS */
        $result = $this->sqsService->receiveMessage($queueUrl);

        /** Validate response format */
        if (!$result->hasKey('Messages')) {
            return;
        }

        /** Cycle of queue messages */
        foreach ($result->get('Messages') as $message) {
            try {

                /** $message['Body'] stores post id */
                $postInfo = $this->socialPostRepository->getPostById((int) $message['Body']);
                foreach ($postInfo as $post) {
                    $output->writeln('In progress '.$post['url']);

                    /** If response from post url wrong, delete this post from db */
                    if (false === HttpHelper::validUrl($post['url'])) {
                        $output->writeln('This post must be deleted '.$post['url']);
                        $this->logger->info('This post must be deleted', [$post['url']]);

                        /** If post social type is Instagram, delete image from AWS S3 */
                        if (SocialType::SOCIAL_TYPE_NAME_INSTAGRAM === $post['socialTypeName']) {
                            $imageSrc = $this->getInstagramImageFromPostInfo($post['post_info']);

                            /** Delete image if exist */
                            if ($this->uploadImageService->imageExists($imageSrc)) {
                                $this->uploadImageService->deleteImage($imageSrc);
                            }
                        }

                        /** Delete post id from db by postId */
                        $this->socialPostRepository->deletePostById((int) $post['id']);
                        continue;
                    }

                    /** If post social type is Instagram, checking AWS S3 image on exist */
                    if (SocialType::SOCIAL_TYPE_NAME_INSTAGRAM === $post['socialTypeName']) {
                        $imageSrc = $this->getInstagramImageFromPostInfo($post['post_info']);

                        /** If image not exist in AWS S3, send this postId to queue for rewriting image data */
                        if (false === $this->uploadImageService->imageExists($imageSrc)) {
                            $this->sqsService->sendMessage($post['id'], $this->getContainer()->getParameter('rewrite_post_queue_url'));
                        }
                    }
                }

                /** Deleting message from AWS SQS */
                $this->sqsService->deleteMessage($message['ReceiptHandle'], $queueUrl);
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage(), [$message['Body']]);
                continue;
            }
        }
    }
}
