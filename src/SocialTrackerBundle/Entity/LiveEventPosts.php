<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 04.01.18
 * Time: 23:16
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LiveEventPosts
 *
 * @ORM\Table(name="live_event_posts", indexes={@ORM\Index(name="live_event_posts_live_event_id_fk", columns={"live_event_id"}), @ORM\Index(name="live_event_posts_social_post_id_fk", columns={"post_id"})})
 * @ORM\Entity(repositoryClass="SocialTrackerBundle\Repository\LiveEventPosts")
 */
class LiveEventPosts
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var LiveEvent
     *
     * @ORM\ManyToOne(targetEntity="LiveEvent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="live_event_id", referencedColumnName="id")
     * })
     */
    private $liveEvent;

    /**
     * @var SocialPost
     *
     * @ORM\ManyToOne(targetEntity="SocialPost")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="post_id", referencedColumnName="id")
     * })
     */
    private $post;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $postId;

    /**
     * @return int
     */
    public function getPostId(): int
    {
        return $this->postId;
    }

    /**
     * @param int $postId
     *
     * @return $this
     */
    public function setPostId(int $postId)
    {
        $this->postId = $postId;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set liveEvent.
     *
     * @param \SocialTrackerBundle\Entity\LiveEvent|null $liveEvent
     *
     * @return LiveEventPosts
     */
    public function setLiveEvent(\SocialTrackerBundle\Entity\LiveEvent $liveEvent = null)
    {
        $this->liveEvent = $liveEvent;

        return $this;
    }

    /**
     * Get liveEvent.
     *
     * @return \SocialTrackerBundle\Entity\LiveEvent|null
     */
    public function getLiveEvent()
    {
        return $this->liveEvent;
    }

    /**
     * Set post.
     *
     * @param \SocialTrackerBundle\Entity\SocialPost|null $post
     *
     * @return LiveEventPosts
     */
    public function setPost(\SocialTrackerBundle\Entity\SocialPost $post = null)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Get post.
     *
     * @return \SocialTrackerBundle\Entity\SocialPost|null
     */
    public function getPost()
    {
        return $this->post;
    }
}
