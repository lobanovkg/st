<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 04.01.18
 * Time: 20:56
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Recording\Post;

/**
 * Class PostData Container for post info
 */
class PostData
{
    /** @var int Social account id */
    private $accountId;

    /** @var int Social post publish date */
    private $date;

    /** @var array Hashtags parsed from post message */
    private $hashtags = [];

    /** @var string Social post message */
    private $message;

    /** @var int Origin social post id */
    private $originalPostId;

    /** @var string Social post url */
    private $postUrl;

    /** @var bool Rewrite social post */
    private $rewritePost = false;

    /** @var bool Send post to queue for search hashtags in comments */
    private $enqueued = false;

    /** @var array Social post images */
    private $thumbnailResources = [];

    /**
     * Get post account id
     *
     * @return int
     */
    public function getAccountId(): int
    {
        return $this->accountId;
    }

    /**
     * Set post account id
     *
     * @param int $accountId Post account id
     *
     * @return $this
     */
    public function setAccountId(int $accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * Get post date create
     *
     * @return int
     */
    public function getDate(): int
    {
        return $this->date;
    }

    /**
     * Set Post date create
     *
     * @param int $date Post date create
     *
     * @return $this
     */
    public function setDate(int $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get social post hashtags parsed from post message
     *
     * @return array
     */
    public function getHashtags(): array
    {
        return $this->hashtags;
    }

    /**
     * Set social post hashtags parsed from post message
     *
     * @param array $hashtags Post message hashtags
     *
     * @return $this
     */
    public function setHashtags(array $hashtags)
    {
        $this->hashtags = $hashtags;

        return $this;
    }

    /**
     * Get social post message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set social post message
     *
     * @param string $message Social post message
     *
     * @return $this
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get origin post id
     *
     * @return int
     */
    public function getOriginalPostId(): int
    {
        return $this->originalPostId;
    }

    /**
     * Set post origin id
     *
     * @param int $originalPostId Origin post id
     *
     * @return $this
     */
    public function setOriginalPostId(int $originalPostId)
    {
        $this->originalPostId = $originalPostId;

        return $this;
    }

    /**
     * Get social post url
     *
     * @return string
     */
    public function getPostUrl(): string
    {
        return $this->postUrl;
    }

    /**
     * Set social post url
     *
     * @param string $postUrl Social post url
     *
     * @return $this
     */
    public function setPostUrl(string $postUrl)
    {
        $this->postUrl = $postUrl;

        return $this;
    }

    /**
     * Is rewrite post
     *
     * @return bool
     */
    public function isRewritePost(): bool
    {
        return $this->rewritePost;
    }

    /**
     * Set rewrite post
     *
     * @param bool $rewritePost
     *
     * @return $this
     */
    public function setRewritePost(bool $rewritePost)
    {
        $this->rewritePost = $rewritePost;

        return $this;
    }

    /**
     * Get marker for adding post to queue
     *
     * @return bool
     */
    public function isEnqueued(): bool
    {
        return $this->enqueued;
    }

    /**
     * Set marker for adding post to queue
     *
     * @param bool $enqueued Marker
     *
     * @return $this
     */
    public function setEnqueued(bool $enqueued)
    {
        $this->enqueued = $enqueued;

        return $this;
    }

    /**
     * Get social post images
     *
     * @return array
     */
    public function getThumbnailResources(): array
    {
        return $this->thumbnailResources;
    }

    /**
     * Set social post images
     *
     * @param array $thumbnailResources Social post images
     *
     * @return $this
     */
    public function setThumbnailResources(array $thumbnailResources)
    {
        $this->thumbnailResources = $thumbnailResources;

        return $this;
    }
}
