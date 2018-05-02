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
 * SocialPostTag
 *
 * @ORM\Table(name="social_post_tag")
 * @ORM\Entity(repositoryClass="SocialTrackerBundle\Repository\SocialPostTag")
 */
class SocialPostTag
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
     * @var SocialTag
     *
     * @ORM\ManyToOne(targetEntity="SocialTag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tag_id", referencedColumnName="id")
     * })
     */
    private $socialTag;

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
     * @ORM\Column(name="tag_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $tagId;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $postId;

    /**
     * @return int
     */
    public function getTagId(): int
    {
        return $this->tagId;
    }

    /**
     * @param int $tagId
     *
     * @return $this
     */
    public function setTagId(int $tagId)
    {
        $this->tagId = $tagId;

        return $this;
    }

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
     * Set socialTag.
     *
     * @param SocialTag|null $socialTag
     *
     * @return SocialPostTag
     */
    public function setSocialTag(SocialTag $socialTag = null)
    {
        $this->socialTag = $socialTag;

        return $this;
    }

    /**
     * Get socialTag.
     *
     * @return SocialTag|null
     */
    public function getSocialTag()
    {
        return $this->socialTag;
    }

    /**
     * Set post.
     *
     * @param \SocialTrackerBundle\Entity\SocialPost|null $post
     *
     * @return SocialPostTag
     */
    public function setPost(SocialPost $post = null)
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
