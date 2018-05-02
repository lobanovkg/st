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
 * LiveEventAccounts
 *
 * @ORM\Table(name="live_event_accounts", indexes={@ORM\Index(name="live_event_accounts_live_event_id_fk", columns={"live_event_id"}), @ORM\Index(name="live_event_accounts_social_account_id_fk", columns={"account_id"})})
 * @ORM\Entity(repositoryClass="SocialTrackerBundle\Repository\LiveEventAccounts")
 */
class LiveEventAccounts
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
     * @var SocialAccount
     *
     * @ORM\ManyToOne(targetEntity="SocialAccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="account_id", referencedColumnName="id")
     * })
     */
    private $account;

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
     * Set liveEvent.
     *
     * @param \SocialTrackerBundle\Entity\LiveEvent|null $liveEvent
     *
     * @return LiveEventAccounts
     */
    public function setLiveEvent(LiveEvent $liveEvent = null)
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
     * Set account.
     *
     * @param \SocialTrackerBundle\Entity\SocialAccount|null $account
     *
     * @return LiveEventAccounts
     */
    public function setAccount(\SocialTrackerBundle\Entity\SocialAccount $account = null)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account.
     *
     * @return \SocialTrackerBundle\Entity\SocialAccount|null
     */
    public function getAccount()
    {
        return $this->account;
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
