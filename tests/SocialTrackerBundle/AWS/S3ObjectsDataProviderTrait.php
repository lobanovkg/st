<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 13.04.18
 * Time: 16:50
 */

declare(strict_types=1);

namespace Tests\SocialTrackerBundle\AWS;

/**
 * Class S3ObjectsDataProviderTrait
 */
trait S3ObjectsDataProviderTrait
{
    /**
     * Get S3 public object
     *
     * @return array
     */
    public function getS3PublicObject()
    {
        return [['uproxx-social-tracker-dev', 'instagram/1/1719328470269342145.jpg']];
    }

    /**
     * Get S3 private object
     *
     * @return array
     */
    public function getS3PrivateObject()
    {
        return [['uproxx-projects', 'social-tracker-configs/scheduler/awslogs.conf']];
    }

    /**
     * Get S3 private bucket
     *
     * @return array
     */
    public function getS3PrivateBucket()
    {
        return [['uproxx-projects']];
    }

    /**
     * Get S3 public bucket
     *
     * @return array
     */
    public function getS3PublicBucket()
    {
        return [['uproxx-social-tracker-dev']];
    }
}
