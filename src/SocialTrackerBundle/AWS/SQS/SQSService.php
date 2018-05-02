<?php
/**
 * Created by PhpStorm.
 * User: Lobanov Kyryll
 * Date: 16.02.18
 * Time: 19:29
 */

declare(strict_types=1);

namespace SocialTrackerBundle\AWS\SQS;

use SocialTrackerBundle\Helper\AwsSQSHelper;

/**
 * Class SQSService
 */
class SQSService
{
    /** @var SqsClient AWS SQS client */
    private $sqsClient;

    /**
     * SQSService constructor.
     *
     * @param \Aws\Sqs\SqsClient $sqsClient
     */
    public function __construct(\Aws\Sqs\SqsClient $sqsClient)
    {
        $this->sqsClient = $sqsClient;
    }

    /**
     * Delete message from AWS SQS
     *
     * @param string $receiptHandle SQS receipt handle
     * @param string $queueUrl      SQS queue url
     */
    public function deleteMessage(string $receiptHandle, string $queueUrl)
    {
        $this->sqsClient->deleteMessage(
            [
                'QueueUrl'      => $queueUrl,
                'ReceiptHandle' => $receiptHandle,
            ]
        );
    }

    /**
     * Receive message from queue
     *
     * @param string $queueUrl SQS queue url
     *
     * @return \Aws\Result
     */
    public function receiveMessage(string $queueUrl)
    {
        return $this->sqsClient->receiveMessage(['QueueUrl' => $queueUrl]);
    }

    /**
     * Send message to queue
     *
     * @param mixed  $message  Social account
     * @param string $queueUrl AWS SQS queue name
     *
     * @return bool
     */
    public function sendMessage($message, string $queueUrl): bool
    {
        if (empty($message)) {
            return false;
        }

        /** Send accounts to queue */
        $params   = [
            'MessageBody' => $message,
            'QueueUrl'    => $queueUrl,
        ];

        /** Set special params for queue type of fifo */
        if (AwsSQSHelper::isQueueTypeFifo($queueUrl)) {
            $params['MessageGroupId'] = $message;
        }
        $this->sqsClient->sendMessage($params);

        return true;
    }
}
