<?php

namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topic\PostExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\SaveImportExportResultTopic;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * This processor serves to finalize batched export process and send email notification upon its completion.
 */
class PostExportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ExportHandler $exportHandler;

    private MessageProducerInterface $producer;

    private LoggerInterface $logger;

    private DoctrineHelper $doctrineHelper;

    private JobManagerInterface $jobManager;

    private ImportExportResultSummarizer $importExportResultSummarizer;

    private NotificationSettings $notificationSettings;

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
        $messageBody = $message->getBody();

        $job = $this->getJobRepository()->findJobById($messageBody['jobId']);
        if ($job === null) {
            $this->logger->critical('Job not found');

            return self::REJECT;
        }

        $job = $job->isRoot() ? $job : $job->getRootJob();
        $files = [];

        foreach ($job->getChildJobs() as $childJob) {
            if (!empty($childJob->getData()) && ($file = $childJob->getData()['file'])) {
                $files[] = $file;
            }
        }

        $fileName = null;
        try {
            $fileName = $this->exportHandler->exportResultFileMerge(
                $messageBody['jobName'],
                $messageBody['exportType'],
                $messageBody['outputFormat'],
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

            $this->sendEmailNotification(
                $messageBody['recipientUserId'],
                $summary,
                $messageBody['notificationTemplate'] ?? ''
            );

            $this->producer->send(
                SaveImportExportResultTopic::getName(),
                [
                    'jobId' => $job->getId(),
                    'type' => $messageBody['exportType'],
                    'entity' => $messageBody['entity'],
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
        return [PostExportTopic::getName()];
    }

    private function sendEmailNotification(int $recipientUserId, array $summary, string $templateName = ''): void
    {
        $message = [
            'from' => $this->notificationSettings->getSender()->toString(),
            'recipientUserId' => $recipientUserId,
            'template' => $templateName ?: ImportExportResultSummarizer::TEMPLATE_EXPORT_RESULT,
            'templateParams' => $summary,
        ];

        $this->producer->send(SendEmailNotificationTemplateTopic::getName(), $message);

        $this->logger->info('Sent notification email.');
    }

    private function getJobRepository(): JobRepository
    {
        return $this->doctrineHelper->getEntityRepository(Job::class);
    }
}
