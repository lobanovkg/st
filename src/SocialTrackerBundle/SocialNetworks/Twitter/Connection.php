<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 09.02.18
 * Time: 23:07
 */

declare(strict_types=1);

namespace SocialTrackerBundle\SocialNetworks\Twitter;

use Abraham\TwitterOAuth\TwitterOAuth;
use SocialTrackerBundle\DependencyInjection\Configuration;
use SocialTrackerBundle\Helper\ArrayHelper;

/**
 * Twitter API connection
 */
class Connection
{
    /** @var array Twitter client api credentials */
    private $credentials;

    /**
     * Connection constructor.
     *
     * @param array $credentials Twitter API credentials
     */
    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Twitter API connection
     *
     * @return TwitterOAuth
     */
    public function getConnection()
    {
        /** Get random twitter api credentials */
        $credentials = ArrayHelper::arrayRandomAssoc($this->credentials);

        $credentials = reset($credentials);

        return new TwitterOAuth(
            $credentials[Configuration::TWITTER_CONSUMER_KEY_NAME],
            $credentials[Configuration::TWITTER_CONSUMER_SECRET_NAME],
            $credentials[Configuration::TWITTER_OAUTH_TOKEN_NAME],
            $credentials[Configuration::TWITTER_OAUTH_TOKEN_SECRET_NAME]
        );
    }
}
