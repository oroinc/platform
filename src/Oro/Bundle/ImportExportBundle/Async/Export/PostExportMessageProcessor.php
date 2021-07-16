<?php

namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
use Oro\Component\MessageQueue\Transport\Exception\Exception;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * This processor serves to finalize batched export process and send email notification upon its completion.
 */
class PostExportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ExportHandler
     */
    private $exportHandler;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var JobManagerInterface
     */
    private $jobManager;

    /**
     * @var ImportExportResultSummarizer
     */
    private $importExportResultSummarizer;

    /**
     * @var NotificationSettings
     */
    private $notificationSettings;

    /**
     * @var int
     */
    private $recipientUserId;

    public function __construct(
        ExportHandler $exportHandler,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        DoctrineHelper $doctrineHelper,
        JobManagerInterface $jobManager,
        ImportExportResultSummarizer $importExportResultSummarizer,
        NotificationSettings $notificationSettings
    ) {
        $this->exportHandler = $exportHandler;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->doctrineHelper = $doctrineHelper;
        $this->jobManager = $jobManager;
        $this->importExportResultSummarizer = $importExportResultSummarizer;
        $this->notificationSettings = $notificationSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (! isset(
            $body['jobId'],
            $body['jobName'],
            $body['exportType'],
            $body['outputFormat'],
            $body['email'],
            $body['entity']
        )) {
            $this->logger->critical('Invalid message');
        }

        if (!($job = $this->getJobRepository()->findJobById((int)$body['jobId']))) {
            $this->logger->critical('Job not found');

            return self::REJECT;
        }

        $job = $job->isRoot() ? $job : $job->getRootJob();
        $files = [];

        foreach ($job->getChildJobs() as $childJob) {
            if (! empty($childJob->getData()) && ($file = $childJob->getData()['file'])) {
                $files[] = $file;
            }
        }

        $fileName = null;
        try {
            $fileName = $this->exportHandler->exportResultFileMerge(
                $body['jobName'],
                $body['exportType'],
                $body['outputFormat'],
                $files
            );
        } catch (RuntimeException $e) {
            $this->logger->critical(
                sprintf('Error occurred during export merge: %s', $e->getMessage()),
                ['exception' => $e]
            );
        }

        if ($fileName !== null) {
            $job->setData(array_merge($job->getData(), ['file' => $fileName]));
            $this->jobManager->saveJob($job);

            $summary = $this->importExportResultSummarizer->processSummaryExportResultForNotification($job, $fileName);

            $this->recipientUserId = $body['recipientUserId'] ?? null;
            $this->sendEmailNotification($body['email'], $summary, $body['notificationTemplate'] ?? null);

            $this->producer->send(
                Topics::SAVE_IMPORT_EXPORT_RESULT,
                [
                    'jobId' => $job->getId(),
                    'type' => $body['exportType'],
                    'entity' => $body['entity'],
                ]
            );
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::POST_EXPORT];
    }

    /**
     * @param string $toEmail
     * @param array $summary
     * @param string $notificationTemplate
     *
     * @throws Exception
     */
    protected function sendEmailNotification($toEmail, array $summary, $notificationTemplate = null)
    {
        $sender = $this->notificationSettings->getSender();
        $message = [
            'sender' => $sender->toArray(),
            'toEmail' => $toEmail,
            'body' => $summary,
            'contentType' => 'text/html',
            'template' => $notificationTemplate ?? ImportExportResultSummarizer::TEMPLATE_EXPORT_RESULT,
        ];

        if ($this->recipientUserId) {
            $message['recipientUserId'] = $this->recipientUserId;
        }

        $this->producer->send(
            NotificationTopics::SEND_NOTIFICATION_EMAIL,
            $message
        );

        $this->logger->info('Sent notification email.');
    }

    /**
     * @return JobRepository|EntityRepository
     */
    private function getJobRepository(): JobRepository
    {
        return $this->doctrineHelper->getEntityRepository(Job::class);
    }
}
