<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 04.01.18
 * Time: 22:39
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Recording\Post;

use Doctrine\ORM\EntityManager;
use SocialTrackerBundle\Crawler\Instagram\Crawler;
use SocialTrackerBundle\Entity\SocialPost;
use SocialTrackerBundle\Entity\SocialPostTag;
use SocialTrackerBundle\Repository\LiveEvent;
use SocialTrackerBundle\Repository\SocialType;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class PostRecord inserting PostData to db
 */
class PostRecord
{
    /** @var Container Symfony DI */
    private $container;

    /** @var EntityManager Doctrine entity manager */
    private $em;

    /** @var \SocialTrackerBundle\Repository\SocialPost Social post repository */
    private $postRepository;

    /** @var string Social type */
    private $socialType;

    /** @var \SocialTrackerBundle\Repository\SocialTag Social tag repository */
    private $socialTagRepository;

    /**
     * PostRecord constructor.
     *
     * @param EntityManager $em        Doctrine entity manager
     * @param Container     $container Symfony DI
     */
    public function __construct(EntityManager $em, Container $container)
    {
        $this->em                  = $em;
        $this->container           = $container;
        $this->postRepository      = $this->em->getRepository('SocialTrackerBundle:SocialPost');
        $this->socialTagRepository = $this->em->getRepository('SocialTrackerBundle:SocialTag');
    }

    /**
     * Main function for inserting social data to db
     *
     * @param array $data Array of PostCollection
     */
    public function record(array $data)
    {
        /** @var PostCollection $postCollection */
        foreach ($data as $postCollection) {
            $this->setSocialTypeByCollection($postCollection);
            $this->insertPostByCollection($postCollection);
            $this->setEventPostRelation($postCollection);
        }
    }

    /**
     * Get existing social post from db by origin post id
     *
     * @param PostCollection $postCollection Social post data
     *
     * @return array
     */
    private function getExistOriginPostIds(PostCollection $postCollection): array
    {
        /** Get all origin post id from PostCollection group by account id */
        $originalPostIds = $postCollection->getAllOriginalPostIdsGroupByAccountId();

        if (!count($originalPostIds)) {
            return [];
        }

        $accountId     = current(array_keys($originalPostIds));
        $originPostIds = $originalPostIds[$accountId];

        return $this->postRepository->getPostIdsByOriginPostIdAndAccountId(
            $accountId,
            $originPostIds,
            \SocialTrackerBundle\Repository\SocialPost::FIELD_NAME_ORIGIN_POST_ID
        );
    }

    /**
     * Inserting social hashtags into db
     *
     * @param array      $hashtags   Social hashtags
     * @param SocialPost $postEntity Social post entity
     */
    private function insertEntityHashtagRelations(array $hashtags, SocialPost $postEntity)
    {
        /** If social hashtags empty return */
        if (!count($hashtags)) {
            return;
        }

        foreach ($hashtags as $hashtag) {
            $socialTagEntity = $this->socialTagRepository->findOneBy(['name' => $hashtag]);

            /** Create social tag if not exist */
            if (null === $socialTagEntity) {
                return;
            }

            /** Create social post relation with hashtag */
            $postTagEntity = new SocialPostTag();
            $postTagEntity->setPost($postEntity)->setSocialTag($socialTagEntity);
            $this->em->persist($postTagEntity);
        }
    }

    /**
     * Insert hashtags relation for existing social post
     *
     * @param array      $hashtags   Post hashtags
     * @param SocialPost $postEntity Social post entity
     */
    private function insertHashtagsRelation(array $hashtags, SocialPost $postEntity)
    {
        $socialPostTagRepository = $this->em->getRepository('SocialTrackerBundle:SocialPostTag');

        /** Get social post entity */
        $socialPostEntity = $this->postRepository->findOneBy(
            ['originPostId' => $postEntity->getOriginPostId(), 'accountId' => $postEntity->getAccountId()]
        );

        foreach ($hashtags as $hashtag) {

            /** Get for check exist post tag relation */
            $socialTagEntity = $this->socialTagRepository->findOneBy(['name' => $hashtag]);

            /** Get for check exist post tag relation */
            $postTagRelationEntity = $socialPostTagRepository->findOneBy(
                ['postId' => $socialPostEntity->getId(), 'tagId' => $socialTagEntity->getId()]
            );

            /** Insert post tag relation if it not exist */
            if (null === $postTagRelationEntity) {
                $this->em->getConnection()->insert(
                    'social_post_tag',
                    ['post_id' => $socialPostEntity->getId(), 'tag_id' => $socialTagEntity->getId()]
                );
            }
        }
    }

    /**
     * Insert into db social post hashtags
     *
     * @param array $hashtags Post hashtags
     */
    private function insertNewHashtag(array $hashtags)
    {
        /** If social hashtags empty return */
        if (!count($hashtags)) {
            return;
        }

        foreach ($hashtags as $hashtag) {
            $socialTagEntity = $this->socialTagRepository->findOneBy(['name' => $hashtag]);

            /** Create social tag if not exist */
            if (null === $socialTagEntity) {
                $this->em->getConnection()->insert('social_tag', ['name' => $hashtag]);
            }
        }
    }

    /**
     * Inserting post to db by PostCollection
     *
     * @param PostCollection $postCollection Social post data
     *
     * @throws \Exception
     */
    private function insertPostByCollection(PostCollection $postCollection)
    {
        /** Get existing origin post ids */
        $existOriginPostIds = $this->getExistOriginPostIds($postCollection);

        /** @var $post PostData */
        foreach ($postCollection as $post) {

            /** Continue for existing post */
            if (in_array($post->getOriginalPostId(), $existOriginPostIds) && false === $post->isRewritePost()) {
                continue;
            }

            /** If post from Instagram */
            if (SocialType::SOCIAL_TYPE_NAME_INSTAGRAM === $this->socialType) {

                /** upload image to AWS S3 */
                $this->instagramUploadImage($post);

                /** If post new, set marker "postToQueue" for searching hashtags in post comments */
                if (!in_array($post->getOriginalPostId(), $existOriginPostIds)) {
                    $post->setEnqueued(true);
                }
            }

            $dateTime   = new \DateTime();
            $socialPost = new SocialPost();

            /** Filling social post entity for inserting */
            $socialPost->setAccountId($post->getAccountId())
                ->setOriginPostId($post->getOriginalPostId())
                ->setPublishDate($dateTime->setTimestamp($post->getDate()))
                ->setUrl($post->getPostUrl())
                ->setMessage($post->getMessage())
                ->setPostInfo(json_encode($post->getThumbnailResources()));

            /** Insert new hashtags */
            $this->insertNewHashtag($post->getHashtags());

            /** If post must be rewrite */
            if (in_array($post->getOriginalPostId(), $existOriginPostIds) && true === $post->isRewritePost()) {

                /** Update social post */
                $this->postRepository->updateFieldPostInfoByEntity($socialPost);

                /** Insert social hashtags */
                $this->insertHashtagsRelation($post->getHashtags(), $socialPost);
            } else {

                /** Insert new social post */
                $this->em->persist($socialPost);

                /** Insert social hashtags */
                $this->insertEntityHashtagRelations($post->getHashtags(), $socialPost);
            }
        }
        $this->em->flush();
        $this->em->clear();

        /** Send post to queue, for searching hashtags in post comments */
        $this->sendPostsToQueue($postCollection);
    }

    /**
     * Upload instagram images to AWS S3
     *
     * @param PostData $post Social post data
     *
     * @throws \Exception
     */
    private function instagramUploadImage(PostData $post)
    {
        $thumbnailResource = $post->getThumbnailResources();

        /** Validate Instagram post images format */
        if (!isset($thumbnailResource[Crawler::THUMBNAIL_NAME])
            || is_array($thumbnailResource[Crawler::THUMBNAIL_NAME])
            || empty($thumbnailResource[Crawler::THUMBNAIL_NAME])
        ) {
            throw new \Exception(sprintf('Invalid thumbnailResource for accountId %s', $post->getAccountId()));
        }

        $uploadImageService = $this->container->get('social_tracker.aws.s3.service');

        /** upload image to AWS S3 */
        $uploadImageService->setParseImageSrc($thumbnailResource[Crawler::THUMBNAIL_NAME])
            ->setAccountId($post->getAccountId())
            ->setOriginPostId($post->getOriginalPostId())
            ->uploadImage();

        /** Set uploaded image src */
        $thumbnailResource[Crawler::THUMBNAIL_NAME] = $uploadImageService->getUploadedImageSrc();

        /** Overwrite instagram image for inserted new src into db */
        $post->setThumbnailResources($thumbnailResource);
    }

    /**
     * Send post to queue, for searching hashtags in post comments
     *
     * @param PostCollection $postCollection Collection of PostData objects
     */
    private function sendPostsToQueue(PostCollection $postCollection)
    {
        /** Get origin post id from PostCollection for searching hashtags in comments, group by account id */
        $postCommentQueue = $postCollection->getOriginalPostIdsForPostToQueue();

        if (!count($postCommentQueue)) {
            return;
        }

        $accountId     = current(array_keys($postCommentQueue));
        $originPostIds = $postCommentQueue[$accountId];

        /** Get post ids from db by origin post id and account id */
        $postIds = $this->postRepository->getPostIdsByOriginPostIdAndAccountId(
            $accountId,
            $originPostIds,
            \SocialTrackerBundle\Repository\SocialPost::FIELD_NAME_ID
        );

        /** Get AWS SQS service */
        $sqsService = $this->container->get('social_tracker.aws.sqs.service');

        /** Get queue url */
        $queueUrl = $this->container->getParameter('post_comment_grabber');

        foreach ($postIds as $postId) {

            /** Send post id to queue */
            $sqsService->sendMessage($postId, $queueUrl);
        }
    }

    /**
     * Creating live event post relation
     *
     * @param PostCollection $postCollection Social post data
     */
    private function setEventPostRelation(PostCollection $postCollection)
    {
        $accountIds = $postCollection->getAllAccountIds();

        foreach ($accountIds as $accountId) {
            $connection = $this->em->getConnection();
            $stmt       = $connection->prepare(
                'insert IGNORE INTO live_event_posts (post_id, live_event_id)
            SELECT p.id postId, e.id eventId
            FROM social_account a
              JOIN live_event_accounts ea ON a.id = ea.account_id
              JOIN live_event e ON ea.live_event_id = e.id
              JOIN social_post p on a.id = p.account_id
            WHERE e.active = :active and ea.account_id = :accountId
                  '
            );
            $active     = LiveEvent::STATUS_ACTIVE;

            $stmt->bindParam('active', $active);
            $stmt->bindParam('accountId', $accountId);
            $stmt->execute();
        }
    }

    /**
     * Set social type for this class
     *
     * @param PostCollection $postCollection Social post data
     */
    private function setSocialTypeByCollection(PostCollection $postCollection)
    {
        $accountIds           = $postCollection->getAllAccountIds();
        $accountId            = reset($accountIds);
        $socialTypeRepository = $this->em->getRepository('SocialTrackerBundle:SocialType');
        $this->socialType     = $socialTypeRepository->getSocialTypeNameByAccountId($accountId);
    }
}
