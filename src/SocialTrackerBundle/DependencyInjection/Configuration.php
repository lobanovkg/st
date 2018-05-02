<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 05.03.18
 * Time: 21:59
 */

declare(strict_types=1);

namespace SocialTrackerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 */
class Configuration implements ConfigurationInterface
{
    const NODE_AWS = 'aws';
    const NODE_AWS_SDK = 'sdk';
    const NODE_AWS_S3 = 's3';
    const NODE_ROOT = 'social_tracker';
    const NODE_TWITTER_CLIENTS = 'clients';
    const NODE_TWITTER_CLIENT_API = 'twitter_client_api';

    const AWS_SDK_REGION_NAME = 'region';
    const AWS_SDK_VERSION_NAME = 'version';

    const AWS_S3_BUCKET_NAME = 'bucket_name';

    const TWITTER_CONSUMER_KEY_NAME = 'consumer_key';
    const TWITTER_CONSUMER_SECRET_NAME = 'consumer_secret';
    const TWITTER_OAUTH_TOKEN_NAME = 'oauth_token';
    const TWITTER_OAUTH_TOKEN_SECRET_NAME = 'oauth_token_secret';

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root(self::NODE_ROOT);

        $rootNode
            ->children()
                ->arrayNode(self::NODE_TWITTER_CLIENT_API)
                    ->children()
                        ->arrayNode(self::NODE_TWITTER_CLIENTS)
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode(self::TWITTER_CONSUMER_KEY_NAME)->isRequired()->end()
                                    ->scalarNode(self::TWITTER_CONSUMER_SECRET_NAME)->isRequired()->end()
                                    ->scalarNode(self::TWITTER_OAUTH_TOKEN_NAME)->isRequired()->end()
                                    ->scalarNode(self::TWITTER_OAUTH_TOKEN_SECRET_NAME)->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode(self::NODE_AWS)
                    ->children()
                        ->arrayNode(self::NODE_AWS_SDK)
                            ->children()
                                ->scalarNode(self::AWS_SDK_REGION_NAME)->isRequired()->end()
                                ->scalarNode(self::AWS_SDK_VERSION_NAME)->isRequired()->end()
                            ->end()
                        ->end()
                        ->arrayNode(self::NODE_AWS_S3)
                            ->children()
                                ->scalarNode(self::AWS_S3_BUCKET_NAME)->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
