<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 30.01.18
 * Time: 16:34
 */

declare(strict_types=1);

namespace SocialTrackerBundle\AWS\S3;

use Aws\S3\S3Client as ClientS3;
use SocialTrackerBundle\DependencyInjection\Configuration;

/**
 * Class S3Client - set credentials and return AWS S3Client
 */
class S3Client
{
    /**
     * @var ClientS3 AWS S3 Client
     */
    private $client;

    /**
     * SQSClient constructor.
     *
     * @param array $credentials AWS SDK credentials
     */
    public function __construct(array $credentials)
    {
        $this->client = new ClientS3(
            [
                'region'  => $credentials[Configuration::AWS_SDK_REGION_NAME],
                'version' => $credentials[Configuration::AWS_SDK_VERSION_NAME],
            ]
        );
    }

    /**
     * @return ClientS3
     */
    public function getS3Client()
    {
        return $this->client;
    }
}
