<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 30.01.18
 * Time: 16:34
 */

declare(strict_types=1);

namespace SocialTrackerBundle\AWS\S3;

use SocialTrackerBundle\Exception\UploadImageException;
use Aws\S3\S3Client;

/**
 * Service for managing image into AWS S3
 */
class S3Service
{
    /** @var int Is needed to create an image path for AWS S3 */
    private $accountId;

    /** @var string AWS S3 bucket name */
    private $bucketName;

    /** @var string Loaded image content type from curl */
    private $contentType;

    /** @var string Loaded image from curl_exec */
    private $loadedImage;

    /** @var int It needed to create image name for AWS S3 */
    private $originPostId;

    /** @var string Origin image src for parser */
    private $parseImageSrc;

    /** @var \Aws\S3\S3Client AWS S3 client */
    private $s3Client;

    /** @var string Uploaded image src from AWS S3 response */
    private $uploadedImageSrc;

    /**
     * S3Service constructor.
     *
     * @param S3Client $s3Client     AWS S3 client
     * @param string   $s3BucketName AWS S3 bucket name
     */
    public function __construct(S3Client $s3Client, string $s3BucketName)
    {
        $this->s3Client   = $s3Client;
        $this->bucketName = $s3BucketName;
    }

    /**
     * Delete image from AWS S3
     *
     * @param string $imageSrc AWS S3 image src
     *
     * @return \Aws\Result
     */
    public function deleteImage(string $imageSrc)
    {
        $imageSrc = $this->getRelativePath($imageSrc);

        return $this->s3Client->deleteObject(['Bucket' => $this->bucketName, 'Key' => $imageSrc]);
    }

    /**
     * Return uploaded image src from AWS S3
     *
     * @return string
     */
    public function getUploadedImageSrc(): string
    {
        return $this->uploadedImageSrc;
    }

    /**
     * Checking image exists in AWS S3
     *
     * @param string $imageSrc AWS S3 image srs
     *
     * @return bool
     */
    public function imageExists(string $imageSrc): bool
    {
        if (empty($imageSrc)) {
            return false;
        }

        $imageSrc = $this->getRelativePath($imageSrc);

        return $this->s3Client->doesObjectExist($this->bucketName, $imageSrc);
    }

    /**
     * Set account id
     *
     * @param int $accountId Social account id
     *
     * @return $this
     */
    public function setAccountId(int $accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * Set origin post id
     *
     * @param mixed $originPostId Origin post id
     *
     * @return $this
     */
    public function setOriginPostId($originPostId)
    {
        $this->originPostId = $originPostId;

        return $this;
    }

    /**
     * Set parse image src
     *
     * @param string $src Origin image src for parser
     *
     * @return $this
     */
    public function setParseImageSrc(string $src)
    {
        $this->parseImageSrc = $src;

        return $this;
    }

    /**
     * This method start main functions for upload image into AWS S3
     */
    public function uploadImage(): void
    {
        $this->validateInsertParameters();
        $this->loadParseImage();
        $this->putImageToS3();
    }

    /**
     * Return path for saving image into S3
     *
     * @return string
     */
    private function getDirPath(): string
    {
        return 'instagram/'.$this->accountId.'/';
    }

    /**
     * Return file name for save into AWS S3
     *
     * @param string $fileName For saving in AWS S3
     *
     * @return string
     */
    private function getFileName(string $fileName): string
    {
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        return $this->originPostId.'.'.$fileExtension;
    }

    /**
     * Get AWS S3 key
     *
     * @return string
     */
    private function getKey()
    {
        return $this->getDirPath().$this->getFileName($this->parseImageSrc);
    }

    /**
     * Get relative path from absolute
     *
     * @param string $imageSrc AWS S3 image src
     *
     * @return string
     */
    private function getRelativePath(string $imageSrc)
    {
        $relativePath = parse_url($imageSrc, PHP_URL_PATH);

        return ltrim($relativePath, '/');
    }

    /**
     * Parse Image from src
     *
     * @throws UploadImageException
     */
    private function loadParseImage()
    {
        try {
            $ch = curl_init($this->parseImageSrc);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

            /** set 5 second timeout */
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            /** @var $loadedImage - loaded image */
            $this->loadedImage = curl_exec($ch);

            /** @var $contentType - loaded image content type */
            $this->contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

            /** @var $httpCode - ger response code */
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (200 !== (int) $httpCode) {
                throw new UploadImageException('Bad Upload Image request!');
            }
        } catch (\Exception $e) {
            throw new UploadImageException('Load Parse Image error!');
        }
    }

    /**
     * Put image to AWS S3
     */
    private function putImageToS3()
    {
        $response = $this->s3Client->putObject(
            [
                'Bucket'       => $this->bucketName,
                'Key'          => $this->getKey(),
                'Body'         => $this->loadedImage,
                'ACL'          => 'public-read',
                'StorageClass' => 'REDUCED_REDUNDANCY',
                'ContentType'  => $this->contentType,
            ]
        );

        $response = $response->toArray();

        /** Validate uploaded response */
        $this->validateUploadResponse($response);

        /** Set uploaded image src */
        $this->setUploadedImageSrc($response);
    }

    /**
     * Set upload image src
     *
     * @param array $response AWS S3 response
     */
    private function setUploadedImageSrc(array $response)
    {
        $this->uploadedImageSrc = $response['@metadata']['effectiveUri'];
    }

    /**
     * Validate inserted parameters
     *
     * @throws UploadImageException
     */
    private function validateInsertParameters()
    {
        if (empty($this->parseImageSrc) || (!$this->s3Client instanceof S3Client)) {
            throw new UploadImageException('Invalid Upload Image parameters!');
        }
    }

    /**
     * Validate uploaded response
     *
     * @param array $response AWS S3 response
     *
     * @throws UploadImageException
     */
    private function validateUploadResponse(array $response)
    {
        if (!isset($response['@metadata']) || empty($response['@metadata'])) {
            throw new UploadImageException('Undefined Upload Image response @metadata!');
        }
        if (!isset($response['@metadata']['statusCode']) || 200 !== (int) $response['@metadata']['statusCode']) {
            throw new UploadImageException('Undefined Upload Image response @metadata statusCode!');
        }
        if (!isset($response['@metadata']['effectiveUri']) || empty($response['@metadata']['effectiveUri'])) {
            throw new UploadImageException('Undefined Upload Image response @metadata effectiveUri!');
        }
    }
}
