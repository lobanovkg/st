<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 09.01.18
 * Time: 13:33
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Crawler\Twitter;

use Abraham\TwitterOAuth\TwitterOAuth;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SocialTrackerBundle\Helper\SocialHelper;
use SocialTrackerBundle\Recording\Post\PostCollection;
use SocialTrackerBundle\Recording\Post\PostData;
use Symfony\Component\DependencyInjection\Container;

/**
 * Twitter Crawler
 */
class Crawler
{
    /** @var array Twitter Parse Accounts */
    private $accounts;

    /** @var Container Symfony DI */
    private $container;

    /** @var bool Debug mode */
    private $debug = false;

    /** @var Logger Symfony Monolog */
    private $logger;

    /** @var TwitterOAuth Twitter API Connection */
    private $twitterConnection;

    /**
     * Crawler constructor.
     *
     * @param Container $container Symfony DI
     */
    public function __construct(Container $container)
    {
        $this->container         = $container;
        $this->twitterConnection = $this->container->get('social_tracker.twitter.api_connection')->getConnection();
    }

    /**
     * Main function for parse info from Twitter accounts
     *
     * @return array
     */
    public function grab(): array
    {
        $postCollections = [];

        /** Loop for twitter accounts */
        foreach ($this->accounts as $accountId => $account) {
            /** Try connect to Twitter API */
            try {
                $content = $this->twitterConnection->get(
                    'statuses/user_timeline',
                    ['screen_name' => $account, 'trim_user' => true, 'exclude_replies' => true, 'include_rts' => false]
                );
            } catch (\Exception $e) {
                $this->setDebugWarning($e->getMessage());
                continue;
            }

            /** Convert array-object response to multiply array */
            $accountInfo = json_decode(json_encode($content), true);

            /** Checking valid account info */
            if ($this->isValidAccount($accountInfo)) {
                $this->setDebugWarning('Invalid account id', [$accountId]);
                continue;
            }

            /** Delete from parse info invalid data */
            $this->accountPostsFilter($accountInfo);

            /** Set PostCollection of Twitter PostData */
            $postCollections[] = $this->parseAccountPosts($accountInfo, $accountId);
        }

        return $postCollections;
    }

    /**
     * Set Twitter accounts for parse info
     *
     * @param array $accounts Twitter accounts
     */
    public function setAccounts(array $accounts)
    {
        $this->accounts = $accounts;
    }

    /**
     * Set debug mode, write log
     *
     * @param bool $debug Debug mode var
     *
     * @return $this
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;

        if ($this->debug) {
            $this->logger = new Logger('twitter-grabber');
            $this->logger->pushHandler(new StreamHandler($this->container->getParameter('twitter_grabber_log_path'), Logger::WARNING));
        }

        return $this;
    }

    /**
     * Delete from parse info invalid data
     *
     * @param array $accountInfo Parsed account info
     */
    private function accountPostsFilter(array &$accountInfo)
    {
        foreach ($accountInfo as $key => $info) {
            if (!isset($info['text'])
                || !isset($info['id'])
                || !isset($info['created_at'])
                || !isset($info['entities'])
                || (!isset($info['entities']['urls']) && !isset($accountInfo['entities']['media']))
            ) {
                unset($accountInfo[$key]);
            }
        }
    }

    /**
     * Get post main image
     *
     * @param array $images Parsed account images
     *
     * @return string
     */
    private function getImages(array $images): string
    {
        /** Validate source schema */
        if (!isset($images['entities'])) {
            return '';
        }
        $entities = $images['entities'];

        /** Validate source schema */
        if (!isset($entities['media']) || !isset($entities['media'][0]) || !isset($entities['media'][0]['media_url'])) {
            return '';
        }

        return (string) $entities['media'][0]['media_url'];
    }

    /**
     * Get post url
     *
     * @param array $post Parsed post info
     *
     * @return string
     */
    private function getUrl(array $post): string
    {
        if (!isset($post['entities'])) {
            return '';
        }

        $entities = $post['entities'];
        if (isset($entities['media']) && isset($entities['media'][0]) && isset($entities['media'][0]['expanded_url'])) {
            return (string) $entities['media'][0]['expanded_url'];
        }

        if (isset($entities['urls']) && isset($entities['urls'][0]) && isset($entities['urls'][0]['expanded_url'])) {
            return (string) $entities['urls'][0]['expanded_url'];
        }

        return '';
    }

    /**
     * Validate parsed account
     *
     * @param array $accountInfo Parsed account info
     *
     * @return bool
     */
    private function isValidAccount(array $accountInfo): bool
    {
        if (isset($accountInfo['error'])) {
            return true;
        }

        return false;
    }

    /**
     * Filling PostCollection of PostData
     *
     * @param array $accountPosts Parsed account info
     * @param int   $accountId    Twitter account id
     *
     * @return PostCollection
     */
    private function parseAccountPosts(array $accountPosts, int $accountId): PostCollection
    {
        $collection = new PostCollection();

        foreach ($accountPosts as $index => $post) {
            $postData = new PostData();

            /** Filling PostData */
            $postData->setAccountId($accountId)
                ->setOriginalPostId((int) $post['id'])
                ->setDate((int) strtotime($post['created_at']))
                ->setThumbnailResources(['src' => $this->getImages($post)])
                ->setPostUrl($this->getUrl($post))
                ->setHashtags(SocialHelper::parseHashTagsFromString($post['text']))
                ->setMessage($post['text']);

            /** Set PostData to PostCollection */
            $collection->offsetSet($index, $postData);
        }

        return $collection;
    }

    /**
     * Set message to log file
     *
     * @param string $message Log message
     * @param array  $context Log context
     */
    private function setDebugWarning(string $message, $context = [])
    {
        if ($this->debug) {
            $this->logger->warning($message, $context);
        }
    }
}
