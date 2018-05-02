<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 04.01.18
 * Time: 19:57
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Crawler\Instagram;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SocialTrackerBundle\Helper\SocialHelper;
use SocialTrackerBundle\Recording\Post\PostCollection;
use SocialTrackerBundle\Recording\Post\PostData;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class Crawler parse data from account
 */
class Crawler
{
    const POST_TYPE_NAME = 'post_type';
    const THUMBNAIL_NAME = 'thumbnail';

    /** @var array Accounts for parsing */
    private $accounts = [];

    /** @var Container Symfony DI */
    private $container;

    /** @var bool Debug mode */
    private $debug = false;

    /** @var Logger Client for writing log */
    private $logger;

    /**
     * Crawler constructor.
     *
     * @param Container $container Symfony DI
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Starting grab info from accounts
     *
     * @return array
     *
     * @throws \Exception
     */
    public function grab(): array
    {
        $postCollections = [];

        foreach ($this->accounts as $accountId => $account) {
            try {
                /** Instagram request with GET variable "__a=1", return all page data to json format */
                $json = file_get_contents('https://www.instagram.com/'.$account.'/?__a=1');
            } catch (\Exception $e) {
                $this->setDebugWarning($e->getMessage());

                /** If instagram server request have 429 code, return account to queue  */
                if (false === strpos($e->getMessage(), 'HTTP/1.1 429')) {
                    continue;
                }
                throw new \Exception('Instagram grab exception!');
            }
            /** Decode Instagram json response and validate */
            $accountInfo = json_decode($json, true);

            /** Validate instagram account */
            if (!is_array($accountInfo) || false === $this->isValidAccount($accountInfo)) {
                $this->setDebugWarning('Parse error for account id', [$accountId]);
                continue;
            }

            /** Get account post from Instagram response */
            $accountPosts = $this->getAccountPosts($accountInfo);

            /** Filling PostCollection of PostData by response and set to array */
            $postCollections[] = $this->parseAccountPosts($accountPosts, $accountId);
        }

        /**
         * Return array of PostCollection
         */
        return $postCollections;
    }

    /**
     * Set Instagram accounts for parse info
     *
     * @param array $accounts Instagram accounts
     *
     * @return $this
     */
    public function setAccounts(array $accounts)
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Enable Debug mode
     *
     * @param bool $debug Enable debug mode
     *
     * @return $this
     */
    public function setDebug(bool $debug)
    {
        /** @var bool Enable debug */
        $this->debug = $debug;

        if ($this->debug) {
            $this->logger = new Logger('instagram-grabber');
            $this->logger->pushHandler(new StreamHandler($this->container->getParameter('instagram_grabber_log_path'), Logger::WARNING));
        }

        return $this;
    }

    /**
     * Get parsed account info
     *
     * @param array $accountInfo Parsed account info
     *
     * @return array
     */
    private function getAccountPosts(array $accountInfo): array
    {
        if (!isset($accountInfo['graphql']['user']) || !isset($accountInfo['graphql']['user']['edge_owner_to_timeline_media'])
            || !isset($accountInfo['graphql']['user']['edge_owner_to_timeline_media']['edges'])
        ) {
            return [];
        }

        return (array) $accountInfo['graphql']['user']['edge_owner_to_timeline_media']['edges'];
    }

    /**
     * Validate parsed account info
     *
     * @param array $accountInfo Parsed account info
     *
     * @return bool
     */
    private function isValidAccount(array $accountInfo): bool
    {
        if (!isset($accountInfo['graphql']['user']) || !isset($accountInfo['graphql']['user']['is_private'])
            || $accountInfo['graphql']['user']['is_private']
        ) {
            return false;
        }

        return true;
    }

    /**
     * Creating PostCollection and filling it by parsed info
     *
     * @param array $accountPosts Parsed account info
     * @param int   $accountId    Parsed account id
     *
     * @return PostCollection
     */
    private function parseAccountPosts(array $accountPosts, int $accountId): PostCollection
    {
        $collection = new PostCollection();

        /** Cycle for parsed account info */
        foreach ($accountPosts as $index => $post) {
            $post = $post['node'];
            $postCaption = '';

            /** Checking, if post message empty, set empty string */
            if (isset($post['edge_media_to_caption']['edges'][0]['node']['text'])) {
                $postCaption = $post['edge_media_to_caption']['edges'][0]['node']['text'];
            }

            $postData = new PostData();

            /** Filling PostData */
            $postData->setAccountId($accountId)
                ->setOriginalPostId((int) $post['id'])
                ->setThumbnailResources(
                    [
                        self::POST_TYPE_NAME => $post['__typename'],
                        self::THUMBNAIL_NAME => $post['display_url'],
                    ]
                )
                ->setPostUrl('https://www.instagram.com/p/'.$post['shortcode'].'/')
                ->setDate((int) $post['taken_at_timestamp'])
                ->setHashtags(SocialHelper::parseHashTagsFromString($postCaption))
                ->setMessage($postCaption);

            /** Set PostData to PostCollection */
            $collection->offsetSet($index, $postData);
        }

        return $collection;
    }

    /**
     * Write message to log file
     *
     * @param string $message Log message
     * @param array  $context Log context
     */
    private function setDebugWarning(string $message, array $context = [])
    {
        if ($this->debug) {
            $this->logger->warning($message, $context);
        }
    }
}
