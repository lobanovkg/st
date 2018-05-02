<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 16.03.18
 * Time: 18:48
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Crawler\Instagram\Validate;

use SocialTrackerBundle\Crawler\Instagram\Crawler;
use SocialTrackerBundle\Exception\ValidateCrawlerResponseException;
use SocialTrackerBundle\Helper\HttpHelper;
use SocialTrackerBundle\Recording\Post\PostCollection;
use SocialTrackerBundle\Recording\Post\PostData;

/**
 * Class ValidateCrawlerResponseStructure
 */
class ValidateCrawlerResponseStructure
{
    /** @var string Error message */
    private $errorMessage;

    /**
     * Get error message
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Validate instagram crawler response
     *
     * @param array $crawlerResponse Instagram crawler response
     *
     * @return bool
     */
    public function isValid(array $crawlerResponse): bool
    {
        try {
            if (0 === count($crawlerResponse)) {
                throw new ValidateCrawlerResponseException('Empty crawler response!');
            }

            /** @var PostCollection $postCollection */
            foreach ($crawlerResponse as $postCollection) {
                $this->validatePostData($postCollection);
                $this->validateDate($postCollection);
                $this->validateOriginalPostId($postCollection);
                $this->validatePostUrl($postCollection);
                $this->validateMessage($postCollection);
                $this->validateHashtags($postCollection);
                $this->validateThumbnailResources($postCollection);
            }
        } catch (ValidateCrawlerResponseException $e) {
            $this->errorMessage = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * Validate PostData collection
     *
     * @param PostCollection $postCollection PostData collection
     *
     * @throws ValidateCrawlerResponseException
     */
    private function validatePostData(PostCollection $postCollection)
    {
        if (0 === count($postCollection)) {
            throw new ValidateCrawlerResponseException('Empty PostData collection!');
        }
    }

    /**
     * Validate date
     *
     * @param PostCollection $postCollection PostData collection
     *
     * @throws ValidateCrawlerResponseException
     */
    private function validateDate(PostCollection $postCollection)
    {
        /** @var PostData $post */
        foreach ($postCollection as $post) {
            if ($post->getDate() > 0) {
                break;
            }
            throw new ValidateCrawlerResponseException('Invalid date format!');
        }
    }

    /**
     * Validate original post id
     *
     * @param PostCollection $postCollection PostData collection
     *
     * @throws ValidateCrawlerResponseException
     */
    private function validateOriginalPostId(PostCollection $postCollection)
    {
        /** @var PostData $post */
        foreach ($postCollection as $post) {
            if ($post->getOriginalPostId() > 0) {
                break;
            }
            throw new ValidateCrawlerResponseException('Invalid original post id format!');
        }
    }

    /**
     * Validate post url
     *
     * @param PostCollection $postCollection PostData collection
     *
     * @throws ValidateCrawlerResponseException
     */
    private function validatePostUrl(PostCollection $postCollection)
    {
        /** @var PostData $post */
        foreach ($postCollection as $post) {
            if (HttpHelper::validUrl($post->getPostUrl())) {
                break;
            }
            throw new ValidateCrawlerResponseException('Invalid post url format!');
        }
    }

    /**
     * Validate message
     *
     * @param PostCollection $postCollection PostData collection
     *
     * @throws ValidateCrawlerResponseException
     */
    private function validateMessage(PostCollection $postCollection)
    {
        $marker = 0;

        /** @var PostData $post */
        foreach ($postCollection as $post) {
            if (strlen($post->getMessage()) > 0) {
                ++$marker;
                break;
            }
        }
        if (0 === $marker) {
            throw new ValidateCrawlerResponseException('Invalid post message format!');
        }
    }

    /**
     * Validate post hashtags
     *
     * @param PostCollection $postCollection PostData collection
     *
     * @throws ValidateCrawlerResponseException
     */
    private function validateHashtags(PostCollection $postCollection)
    {
        $marker = 0;

        /** @var PostData $post */
        foreach ($postCollection as $post) {
            if (count($post->getHashtags()) > 0) {
                ++$marker;
                break;
            }
        }
        if (0 === $marker) {
            throw new ValidateCrawlerResponseException('Invalid post hashtags format!');
        }
    }

    /**
     * Validate post thumbnail resources
     *
     * @param PostCollection $postCollection PostData collection
     *
     * @throws ValidateCrawlerResponseException
     */
    private function validateThumbnailResources(PostCollection $postCollection)
    {
        /** @var PostData $post */
        foreach ($postCollection as $post) {
            if (count($post->getThumbnailResources()) === 2) {
                $thumbnailResources = $post->getThumbnailResources();
                if (strlen($thumbnailResources[Crawler::POST_TYPE_NAME]) === 0 || strlen($thumbnailResources[Crawler::THUMBNAIL_NAME]) === 0) {
                    throw new ValidateCrawlerResponseException('Empty items in post ThumbnailResources!');
                }
                break;
            }
            throw new ValidateCrawlerResponseException('Invalid post thumbnail resources format!');
        }
    }
}
