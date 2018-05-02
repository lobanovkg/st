<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 04.01.18
 * Time: 21:01
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Recording\Post;

/**
 * Class PostCollection for PostData
 */
class PostCollection extends \ArrayObject
{
    /**
     * PostCollection constructor.
     */
    public function __construct()
    {
        /**
         * [] - The input parameter accepts an array or an Object.
         * ArrayObject::STD_PROP_LIST Properties of the object have their normal functionality when accessed as list (var_dump, foreach, etc.).
         */
        parent::__construct([], \ArrayObject::STD_PROP_LIST);
    }

    /**
     * Get all origin post id from PostCollection group by account id
     *
     * @return array
     */
    public function getAllOriginalPostIdsGroupByAccountId(): array
    {
        $result = [];
        /** @var PostData $post */
        foreach ($this as $post) {
            $result[$post->getAccountId()][] = $post->getOriginalPostId();
        }

        return $result;
    }

    /**
     * Get all unique account id from PostCollection
     *
     * @return array
     */
    public function getAllAccountIds(): array
    {
        $result = [];
        /** @var PostData $post */
        foreach ($this as $post) {
            $result[] = $post->getAccountId();
        }

        return array_values(array_unique($result));
    }

    /**
     * Get origin post id from PostCollection for searching hashtags in comments, group by account id
     *
     * @return array
     */
    public function getOriginalPostIdsForPostToQueue(): array
    {
        $result = [];
        /** @var PostData $post */
        foreach ($this as $post) {
            if ($post->isEnqueued()) {
                $result[$post->getAccountId()][] = $post->getOriginalPostId();
            }
        }

        return $result;
    }
}
