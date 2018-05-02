<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 05.03.18
 * Time: 18:22
 */

declare(strict_types=1);

namespace SocialTrackerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class SocialTrackerExtension
 */
class SocialTrackerExtension extends Extension
{

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');

        $container->setParameter(
            'social_tracker.twitter_client_api.clients',
            $config[Configuration::NODE_TWITTER_CLIENT_API][Configuration::NODE_TWITTER_CLIENTS]
        );

        $container->setParameter('social_tracker.aws.sdk.credentials', $config[Configuration::NODE_AWS][Configuration::NODE_AWS_SDK]);
        $container->setParameter(
            'social_tracker.aws.s3.bucket_name',
            $config[Configuration::NODE_AWS][Configuration::NODE_AWS_S3][Configuration::AWS_S3_BUCKET_NAME]
        );
    }
}
