<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 07.01.18
 * Time: 16:23
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Recording\Event;

/**
 * Class EventData
 */
class EventData
{
    const ACCOUNT_USER_NAME_KEY = 'username';
    const ACCOUNT_TYPE_KEY = 'type';

    /** @var array Social accounts */
    private $accounts = [];

    /** @var int Live event active status */
    private $active;

    /** @var int Live event origin id */
    private $id;

    /** @var string Live event name */
    private $name;

    /**
     * Get live event origin id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set live event origin id
     *
     * @param int $id Live event origin id
     *
     * @return $this
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get live event name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set live event name
     *
     * @param string $name Live event name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get live event active status
     *
     * @return int
     */
    public function getActive(): int
    {
        return $this->active;
    }

    /**
     * Set live event active status
     *
     * @param int $active Live event active status
     *
     * @return $this
     */
    public function setActive(int $active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get social accounts
     *
     * @return array
     */
    public function getAccounts(): array
    {
        return $this->accounts;
    }

    /**
     * Set social accounts
     *
     * @param array $accounts Social accounts
     *                        Data format:
     *                        array(
     *                        array(ACCOUNT_TYPE_KEY => socialTypeName_1, ACCOUNT_USER_NAME_KEY => socialAccountName_1),
     *                        array(ACCOUNT_TYPE_KEY => socialTypeName_1, ACCOUNT_USER_NAME_KEY => socialAccountName_2),
     *                        array(ACCOUNT_TYPE_KEY => socialTypeName_2, ACCOUNT_USER_NAME_KEY => socialAccountName_3),
     *                        ...
     *                        );
     *
     * @return $this
     */
    public function setAccounts(array $accounts)
    {
        $this->accounts = $accounts;

        return $this;
    }
}
