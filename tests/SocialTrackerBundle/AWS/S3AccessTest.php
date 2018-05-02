<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 23.02.18
 * Time: 22:21
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\AWS;

use Aws\S3\S3Client;
use PHPUnit\Framework\TestCase;

/**
 * Class S3AccessTest
 *
 * @group social-tracker
 * @group social-tracker-aws
 */
class S3AccessTest extends TestCase
{
    /** Data provider trait */
    use S3ObjectsDataProviderTrait;

    const S3_FULL_CONTROL = 'FULL_CONTROL';
    const S3_WRITE = 'WRITE';
    const S3_WRITE_ACP = 'WRITE_ACP';
    const S3_READ = 'READ';
    const S3_READ_ACP = 'READ_ACP';

    /** @var S3Client AWS S3 Client */
    private static $s3Client;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        self::$s3Client = new S3Client(['region' => 'us-east-1', 'version' => 'latest']);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        self::$s3Client = null;
    }

    /**
     * Test public access to object
     *
     * @param string $bucket
     * @param string $key
     *
     * @dataProvider getS3PublicObject
     */
    public function testPublicObjectAccess(string $bucket, string $key)
    {
        $response = self::$s3Client->getObjectAcl(['Bucket' => $bucket, 'Key' => $key]);
        $response = $response->toArray();

        self::assertCount(2, $response['Grants']);

        foreach ($response['Grants'] as $grant) {
            self::assertContains($grant['Permission'], [self::S3_FULL_CONTROL, self::S3_READ]);
        }
    }

    /**
     * Test private access to object
     *
     * @param string $bucket
     * @param string $key
     *
     * @dataProvider getS3PrivateObject
     */
    public function testPrivateObjectAccess(string $bucket, string $key)
    {
        $response = self::$s3Client->getObjectAcl(['Bucket' => $bucket, 'Key' => $key]);
        $response = $response->toArray();

        self::assertCount(1, $response['Grants']);

        self::assertContains($response['Grants'][0]['Permission'], self::S3_WRITE);
    }

    /**
     * Test public access to bucket
     *
     * @param string $bucket
     *
     * @dataProvider getS3PublicBucket
     */
    public function testPublicBucketAccess(string $bucket)
    {
        $response = self::$s3Client->getBucketAcl(['Bucket' => $bucket]);
        $response = $response->toArray();

        self::assertCount(2, $response['Grants']);

        foreach ($response['Grants'] as $grant) {
            self::assertContains($grant['Permission'], [self::S3_FULL_CONTROL, self::S3_READ]);
        }
    }

    /**
     * Test private access to bucket
     *
     * @param string $bucket
     *
     * @dataProvider getS3PrivateBucket
     *
     * @expectedException \Aws\S3\Exception\S3Exception
     */
    public function testPrivateBucketAccess(string $bucket)
    {
        self::$s3Client->getBucketAcl(['Bucket' => $bucket]);
    }
}
