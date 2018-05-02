<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 13.02.18
 * Time: 17:11
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Logger;

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

/**
 * Log wrapper
 */
class LogWrapper
{
    /** @var string Monolog log level */
    private $logLevel;

    /** @var MonologLogger Monolog Logger */
    private $monologLogger;

    /** @var LoggerSettingsData Logger settings data */
    private $settingsData;

    /**
     * LogWrapper constructor.
     *
     * @param string $logLevel Monolog log level
     */
    public function __construct(string $logLevel)
    {
        $this->logLevel = $logLevel;
    }

    /**
     * Get Monolog Logger
     *
     * @return MonologLogger
     */
    public function getLogger(): MonologLogger
    {
        if (null === $this->monologLogger) {

            /** Set NullHandler */
            $this->monologLogger = new MonologLogger('default');
            $this->monologLogger->pushHandler(new NullHandler());
        }

        return $this->monologLogger;
    }

    /**
     * Init stream handler
     *
     * @return $this
     */
    public function initStreamHandler()
    {
        if ($this->settingsData->isEnable()) {
            $this->setMonologLogger();
            $this->monologLogger->pushHandler(new StreamHandler($this->settingsData->getLogFileName(), $this->settingsData->getLogLevel()));
        }

        return $this;
    }

    /**
     * Set logger settings data
     *
     * @param LoggerSettingsData $settingsData Logger settings data
     *
     * @return $this
     */
    public function setLoggerSettingsData(LoggerSettingsData $settingsData)
    {
        $this->settingsData = $settingsData;

        /** If not set custom log level use environment rules */
        if (null === $this->settingsData->getLogLevel()) {
            $this->settingsData->setLogLevel(constant('\Monolog\Logger::'.$this->logLevel));
        }

        return $this;
    }

    /**
     * Set Monolog Logger
     */
    private function setMonologLogger()
    {
        if (null === $this->monologLogger) {
            $this->monologLogger = new MonologLogger($this->settingsData->getChannelName());
        }
    }
}
