<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 23.01.18
 * Time: 22:23
 */

declare(strict_types=1);

namespace SocialTrackerBundle\AWS\SQS;

use SocialTrackerBundle\DependencyInjection\Configuration;

/**
 * Class SQSClient
 */
class SQSClient
{
    /** @var \Aws\Sqs\SqsClient AWS SQS client */
    private $client;

    /**
     * SQSClient constructor.
     *
     * @param array $credentials AWS SDK credentials
     */
    public function __construct(array $credentials)
    {
        $this->client = new \Aws\Sqs\SqsClient(
            [
                'region'  => $credentials[Configuration::AWS_SDK_REGION_NAME],
                'version' => $credentials[Configuration::AWS_SDK_VERSION_NAME],
            ]
        );
    }

    /**
     * Return initialized AWS SQS client
     *
     * @return \Aws\Sqs\SqsClient
     */
    public function getSQSClient()
    {
        return $this->client;
    }
}
