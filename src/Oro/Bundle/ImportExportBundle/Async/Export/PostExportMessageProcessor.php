<?php

namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

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
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ImportExportResultSummarizer
     */
    private $importExportResultSummarizer;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ExportHandler $exportHandler
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     * @param JobStorage $jobStorage
     * @param ImportExportResultSummarizer $importExportResultSummarizer
     * @param ConfigManager $configManager
     */
    public function __construct(
        ExportHandler $exportHandler,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        JobStorage $jobStorage,
        ImportExportResultSummarizer $importExportResultSummarizer,
        ConfigManager $configManager
    ) {
        $this->exportHandler = $exportHandler;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->jobStorage = $jobStorage;
        $this->importExportResultSummarizer = $importExportResultSummarizer;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (! isset($body['jobId'], $body['jobName'], $body['exportType'], $body['outputFormat'], $body['email'])) {
            $this->logger->critical('Invalid message');
        }

        if (! ($job = $this->jobStorage->findJobById($body['jobId']))) {
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

        try {
            $fileName = $this->exportHandler->exportResultFileMerge(
                $body['jobName'],
                $body['exportType'],
                $body['outputFormat'],
                $files
            );

            $summary = $this->importExportResultSummarizer
                ->processSummaryExportResultForNotification($job, $fileName);

            $this->sendNotification($body['email'], $summary);

            $this->logger->info('Sent notification email.');
        } catch (RuntimeException $exception) {
            $this->logger->critical(
                sprintf('Error occurred during export merge: %s', $exception->getMessage()),
                ['exception' => $exception]
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
     */
    protected function sendNotification($toEmail, array $summary)
    {
        $fromEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
        $fromName = $this->configManager->get('oro_notification.email_notification_sender_name');
        $message = [
            'fromEmail' => $fromEmail,
            'fromName' => $fromName,
            'toEmail' => $toEmail,
            'body' => $summary,
            'contentType' => 'text/html',
            'template' => ImportExportResultSummarizer::TEMPLATE_EXPORT_RESULT,
        ];

        $this->producer->send(
            NotificationTopics::SEND_NOTIFICATION_EMAIL,
            $message
        );
    }
}
