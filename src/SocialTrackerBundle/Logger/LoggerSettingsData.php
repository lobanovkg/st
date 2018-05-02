<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 13.02.18
 * Time: 17:16
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Logger;

/**
 * Logger settings data
 */
class LoggerSettingsData
{
    /** @var string Logger channel name */
    private $channelName;

    /** @var bool Activity status */
    private $enable = false;

    /** @var string Log file name */
    private $logFileName;

    /** @var int Log level */
    private $logLevel;

    /**
     * Get logger channel name
     *
     * @return string
     */
    public function getChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * Set logger channel name
     *
     * @param string $channelName Logger channel name
     *
     * @return $this
     */
    public function setChannelName(string $channelName)
    {
        $this->channelName = $channelName;

        return $this;
    }

    /**
     * Get activity status
     *
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->enable;
    }

    /**
     * Set activity status
     *
     * @param bool $enable Activity status
     *
     * @return $this
     */
    public function setEnable(bool $enable)
    {
        $this->enable = $enable;

        return $this;
    }

    /**
     * Get log file name
     *
     * @return string
     */
    public function getLogFileName(): string
    {
        return $this->logFileName;
    }

    /**
     * Set log file name
     *
     * @param string $logFileName Log file name
     *
     * @return $this
     */
    public function setLogFileName(string $logFileName)
    {
        $this->logFileName = $logFileName;

        return $this;
    }

    /**
     * Get log level
     *
     * @return int|null
     */
    public function getLogLevel(): ?int
    {
        return $this->logLevel;
    }

    /**
     * Set log level
     *
     * @param int $logLevel Log level
     *
     * @return $this
     */
    public function setLogLevel(int $logLevel)
    {
        $this->logLevel = $logLevel;

        return $this;
    }
}
