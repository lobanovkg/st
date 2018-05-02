<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 14.02.18
 * Time: 18:34
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Crawler\Instagram;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SocialTrackerBundle\Exception\PostCrawlerException;
use SocialTrackerBundle\Helper\SocialHelper;
use SocialTrackerBundle\Recording\Post\PostCollection;
use SocialTrackerBundle\Recording\Post\PostData;
use SocialTrackerBundle\Repository\SocialAccount;
use SocialTrackerBundle\Repository\SocialType;
use Symfony\Component\DependencyInjection\Container;

/**
 * Grab info from post
 */
class PostCrawler
{
    const POST_TYPE_NAME = 'post_type';
    const THUMBNAIL_NAME = 'thumbnail';

    /** @var Container Symfony DI */
    private $container;

    /** @var bool Debug mode */
    private $debug = false;

    /** @var Logger Client for writing log */
    private $logger;

    /** @var int Post account id */
    private $postAccountId;

    /** @var int Post id for parsing */
    private $postId;

    /** @var string Post url */
    private $postUrl;

    /**
     * PostCrawler constructor.
     *
     * @param Container $container
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
     * @throws PostCrawlerException
     */
    public function grab(): array
    {
        $postCollections = [];

        $em                   = $this->container->get('doctrine.orm.entity_manager');
        $socialPostRepository = $em->getRepository('SocialTrackerBundle:SocialPost');

        /** Get post info from db */
        if ($this->postUrl) {
            $postInfo = $socialPostRepository->getPostByUrl($this->postUrl);
        } else {
            $postInfo = $socialPostRepository->getPostById($this->postId);
        }
        $postInfo = reset($postInfo);

        /** Validate post data */
        if (!is_array($postInfo) || false === $this->isValidPost($postInfo)) {
            $errorMessage = sprintf('Invalid info for PostCrawler, postId is \%', $this->postId);
            $this->setDebugWarning($errorMessage);
            throw new PostCrawlerException($errorMessage);
        }

        $this->postUrl       = $postInfo['url'];
        $this->postAccountId = $postInfo['account_id'];

        try {
            /** Instagram request with GET variable "__a=1", return all page data to json format */
            $json = file_get_contents($postInfo['url'].'?__a=1');
        } catch (\Exception $e) {
            $this->setDebugWarning($e->getMessage());

            /** If instagram server request have 429 code, return account to queue */
            if (false === strpos($e->getMessage(), 'HTTP/1.1 429')) {
                throw new PostCrawlerException('Instagram grab limit is exceeded!');
            }

            /**
             * Return empty array for deleting this post from queue
             */
            return [];
        }
        /** Decode Instagram json response and validate */
        $parsedInfo = json_decode($json, true);
        if (false === $this->isValidPostResponse($parsedInfo)) {
            $this->setDebugWarning('Invalid post id', [$this->postId]);
            throw new PostCrawlerException(sprintf('Invalid post id %s', $this->postId));
        }

        /** Get post data from Instagram response */
        $accountPostInfo = $this->getPostInfo($parsedInfo);

        /** @var SocialAccount $socialAccountRepository */
        $socialAccountRepository = $this->container->get('doctrine.orm.entity_manager')->getRepository('SocialTrackerBundle:SocialAccount');

        /** @var \SocialTrackerBundle\Entity\SocialAccount $accountEntity */
        $accountEntity = $socialAccountRepository->find($this->postAccountId);

        $postComments = $this->getPostComment($accountPostInfo);
        $firstComment = $this->getFirstOwnerComment($postComments, $accountEntity->getUserName());

        /** Filling PostCollection of PostData by response and set to array */
        $postCollections[] = $this->fillPostCollection($accountPostInfo, $firstComment);

        /**
         * Return array of PostCollection
         */
        return $postCollections;
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
            $this->logger = new Logger('instagram-post-grabber');
            $this->logger->pushHandler(new StreamHandler($this->container->getParameter('instagram_post_grabber_log_path'), Logger::INFO));
        }

        return $this;
    }

    /**
     * Set social post id
     *
     * @param int $postId Social post id
     *
     * @return $this
     */
    public function setPostId(int $postId)
    {
        $this->postId = $postId;

        return $this;
    }

    /**
     * Set post url
     *
     * @param string $postUrl Post url
     *
     * @return $this
     */
    public function setPostUrl(string $postUrl)
    {
        $this->postUrl = $postUrl;

        return $this;
    }

    /**
     * Get first owner comment text
     *
     * @param array  $postComments
     * @param string $userName
     *
     * @return string
     */
    private function getFirstOwnerComment(array $postComments, string $userName): string
    {
        $result = [];

        foreach ($postComments as $postCommentInfo) {
            if (!isset($postCommentInfo['node']) || !isset($postCommentInfo['node']['owner']) || !is_array($postCommentInfo['node']['owner'])) {
                continue;
            }
            if ($userName !== $postCommentInfo['node']['owner']['username']) {
                continue;
            }
            $result[$postCommentInfo['node']['created_at']] = $postCommentInfo['node']['text'];
        }
        ksort($result, SORT_NUMERIC);

        return reset($result) ?: '';
    }

    /**
     * Get post info from parsed response
     *
     * @param array $parsedInfo Parsed info
     *
     * @return array
     */
    private function getPostInfo(array $parsedInfo)
    {
        if (!isset($parsedInfo['graphql']['shortcode_media']) || !is_array($parsedInfo['graphql']['shortcode_media'])) {
            return [];
        }

        return $parsedInfo['graphql']['shortcode_media'];
    }

    /**
     * Get post comments
     *
     * @param array $postInfo Post info response
     *
     * @return array
     */
    private function getPostComment(array $postInfo)
    {
        if (!isset($postInfo['edge_media_to_comment']['edges']) || !is_array($postInfo['edge_media_to_comment']['edges'])) {
            return [];
        }

        return $postInfo['edge_media_to_comment']['edges'];
    }

    /**
     * Creating PostCollection and filling it by parsed info
     *
     * @param array  $parsedPostInfo Parsed post info
     * @param string $postComment    First owner comment text
     *
     * @return PostCollection
     */
    private function fillPostCollection(array $parsedPostInfo, string $postComment): PostCollection
    {
        $collection  = new PostCollection();
        $postCaption = '';

        /** Checking, if post message empty, set empty string */
        if (isset($parsedPostInfo['edge_media_to_caption']['edges'][0]['node']['text'])) {
            $postCaption = $parsedPostInfo['edge_media_to_caption']['edges'][0]['node']['text'];
        }

        $postData = new PostData();

        /** Filling PostData */
        $postData->setAccountId((int) $this->postAccountId)
            ->setOriginalPostId((int) $parsedPostInfo['id'])
            ->setThumbnailResources(
                [
                    self::POST_TYPE_NAME => $parsedPostInfo['__typename'],
                    self::THUMBNAIL_NAME => $parsedPostInfo['display_url'],
                ]
            )
            ->setPostUrl($this->postUrl)
            ->setDate((int) $parsedPostInfo['taken_at_timestamp'])
            ->setHashtags(SocialHelper::parseHashTagsFromString($postCaption.$postComment))
            ->setMessage($postCaption)
            ->setRewritePost(true);

        /** Set PostData to PostCollection */
        $collection->offsetSet(0, $postData);

        return $collection;
    }

    /**
     * Checking valid post info
     *
     * @param array $postInfo Post info from db
     *
     * @return bool
     */
    private function isValidPost(array $postInfo)
    {
        if (!is_array($postInfo) || SocialType::SOCIAL_TYPE_NAME_INSTAGRAM !== $postInfo['socialTypeName'] || empty($postInfo['url'])) {
            return false;
        }

        return true;
    }

    /**
     * Validate parsed post info
     *
     * @param array $postInfo Parsed post info
     *
     * @return bool
     */
    private function isValidPostResponse(array $postInfo): bool
    {
        if (!isset($postInfo['graphql']['shortcode_media']['owner']['is_private'])
            || $postInfo['graphql']['shortcode_media']['owner']['is_private']
        ) {
            return false;
        }

        return true;
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
