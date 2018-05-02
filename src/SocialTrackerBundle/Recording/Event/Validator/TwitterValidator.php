<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 10.01.18
 * Time: 17:15
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Recording\Event\Validator;

use Symfony\Component\DependencyInjection\Container;

/**
 * Validate Twitter account
 */
class TwitterValidator implements SocialValidateInterface
{
    /** @var Container Symfony DI */
    private $container;

    /** @var \Abraham\TwitterOAuth\TwitterOAuth Twitter API Connection */
    private $twitterConnection;

    /**
     * TwitterValidator constructor.
     *
     * @param Container $container Symfony DI
     */
    public function __construct(Container $container)
    {
        $this->container         = $container;
        $this->twitterConnection = $this->container->get('social_tracker.twitter.api_connection')->getConnection();
    }

    /**
     * Validate social account
     *
     * @param string $userName
     *
     * @return bool
     */
    public function validateUserAccount(string $userName): bool
    {
        /** Try connect to Twitter API */
        try {
            $content = $this->twitterConnection->get(
                'statuses/user_timeline',
                ['screen_name' => $userName, 'trim_user' => true, 'exclude_replies' => true, 'include_rts' => false]
            );
        } catch (\Exception $e) {
            return false;
        }

        /** Convert array-object response to multiply array */
        $accountInfo = json_decode(json_encode($content), true);

        return $this->isValidAccount($accountInfo);
    }

    /**
     * Validate social account
     *
     * @param array $accountInfo Parsed account info
     *
     * @return bool
     */
    private function isValidAccount(array $accountInfo): bool
    {
        if (isset($accountInfo['error']) || isset($accountInfo['errors'])) {
            return false;
        }

        return true;
    }
}
