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
 * SocialPost
 *
 * @ORM\Table(name="social_post", options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"}, indexes={@ORM\Index(name="social_post_social_account_id_fk", columns={"account_id"})})
 * @ORM\Entity(repositoryClass="SocialTrackerBundle\Repository\SocialPost")
 */
class SocialPost
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
     * @var int
     *
     * @ORM\Column(name="origin_post_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $originPostId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="publish_date", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $publishDate = 'CURRENT_TIMESTAMP';

    /**
     * @var string
     *
     * @ORM\Column(name="post_info", type="text", length=65535, nullable=false)
     */
    private $postInfo;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=2000, nullable=false)
     */
    private $message = null;

    /**
     * @var int
     *
     * @ORM\Column(name="account_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $accountId;

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
     * Set originPostId.
     *
     * @param int $originPostId
     *
     * @return SocialPost
     */
    public function setOriginPostId($originPostId)
    {
        $this->originPostId = $originPostId;

        return $this;
    }

    /**
     * Get originPostId.
     *
     * @return int
     */
    public function getOriginPostId()
    {
        return $this->originPostId;
    }

    /**
     * Set publishDate.
     *
     * @param \DateTime $publishDate
     *
     * @return SocialPost
     */
    public function setPublishDate($publishDate)
    {
        $this->publishDate = $publishDate;

        return $this;
    }

    /**
     * Get publishDate.
     *
     * @return \DateTime
     */
    public function getPublishDate()
    {
        return $this->publishDate;
    }

    /**
     * Set postInfo.
     *
     * @param string $postInfo
     *
     * @return SocialPost
     */
    public function setPostInfo($postInfo)
    {
        $this->postInfo = $postInfo;

        return $this;
    }

    /**
     * Get postInfo.
     *
     * @return string
     */
    public function getPostInfo()
    {
        return $this->postInfo;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return SocialPost
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set message.
     *
     * @param string $message
     *
     * @return SocialPost
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getAccountId(): int
    {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     *
     * @return $this
     */
    public function setAccountId(int $accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }
}
