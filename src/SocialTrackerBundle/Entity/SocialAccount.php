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
 * SocialAccount
 *
 * @ORM\Table(name="social_account", indexes={@ORM\Index(name="social_account_social_type_id_fk", columns={"social_type_id"})})
 * @ORM\Entity(repositoryClass="SocialTrackerBundle\Repository\SocialAccount")
 */
class SocialAccount
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
     * @ORM\Column(name="original_account_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $originalAccountId;

    /**
     * @var string
     *
     * @ORM\Column(name="user_name", type="string", length=255, nullable=false)
     */
    private $userName;

    /**
     * @var SocialType
     *
     * @ORM\ManyToOne(targetEntity="SocialType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="social_type_id", referencedColumnName="id")
     * })
     */
    private $socialType;



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
     * Set originalAccountId.
     *
     * @param int $originalAccountId
     *
     * @return SocialAccount
     */
    public function setOriginalAccountId($originalAccountId)
    {
        $this->originalAccountId = $originalAccountId;

        return $this;
    }

    /**
     * Get originalAccountId.
     *
     * @return int
     */
    public function getOriginalAccountId()
    {
        return $this->originalAccountId;
    }

    /**
     * Set userName.
     *
     * @param string $userName
     *
     * @return SocialAccount
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get userName.
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Set socialType.
     *
     * @param \SocialTrackerBundle\Entity\SocialType|null $socialType
     *
     * @return SocialAccount
     */
    public function setSocialType(\SocialTrackerBundle\Entity\SocialType $socialType = null)
    {
        $this->socialType = $socialType;

        return $this;
    }

    /**
     * Get socialType.
     *
     * @return \SocialTrackerBundle\Entity\SocialType|null
     */
    public function getSocialType()
    {
        return $this->socialType;
    }
}
