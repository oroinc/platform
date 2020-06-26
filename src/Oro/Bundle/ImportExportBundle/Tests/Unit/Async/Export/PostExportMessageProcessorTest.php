<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Export\PostExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Job as JobEntity;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobManagerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class PostExportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const USER_ID = 132;

    /**
     * @var PostExportMessageProcessor
     */
    private $postExportMessageProcessor;

    /**
     * @var NotificationSettings|\PHPUnit\Framework\MockObject\MockObject
     */
    private $notificationSettings;

    /**
     * @var ImportExportResultSummarizer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importExportResultSummarizer;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|JobRepository
     */
    private $jobRepository;

    /**
     * @var JobManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobManager;

    /**
     * @var MessageProducer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageProducer;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var ExportHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $exportHandler;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->exportHandler = $this->createMock(ExportHandler::class);
        $this->messageProducer = $this->createMock(MessageProducer::class);
        $this->jobRepository = $this->createMock(JobRepository::class);
        $this->jobManager = $this->createMock(JobManagerInterface::class);
        $this->importExportResultSummarizer = $this->createMock(ImportExportResultSummarizer::class);
        $this->notificationSettings = $this->createMock(NotificationSettings::class);
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(JobEntity::class)
            ->willReturn($this->jobRepository);

        $this->postExportMessageProcessor = new PostExportMessageProcessor(
            $this->exportHandler,
            $this->messageProducer,
            $this->logger,
            $doctrineHelper,
            $this->jobManager,
            $this->importExportResultSummarizer,
            $this->notificationSettings
        );
    }

    public function testProcessExceptionsAreHandledDuringMerge()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject */
        $message = $this->createMock(MessageInterface::class);
        $messageBody = [
            'jobId' => '1',
            'jobName' => 'job-name',
            'exportType' => 'type',
            'outputFormat' => 'csv',
            'email' => 'test@example.com',
            'notificationTemplate' => 'resultTemplate',
            'entity' => 'Acme',
        ];
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(json_encode($messageBody));

        $job = new Job();
        $childJob = new Job();
        $childJob->setRootJob($job);
        $job->setChildJobs([$childJob]);

        $this->jobRepository
            ->expects(self::once())
            ->method('findJobById')
            ->willReturn($childJob);

        $this->importExportResultSummarizer
            ->expects(self::any())
            ->method('processSummaryExportResultForNotification')
            ->willReturn([]);

        $this->exportHandler
            ->expects(self::once())
            ->method('exportResultFileMerge')
            ->willReturn('acme_filename');

        $exceptionMessage = 'Exception message';
        $exception = new RuntimeException($exceptionMessage);
        $this->exportHandler
            ->expects(self::once())
            ->method('exportResultFileMerge')
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('critical')
            ->with(
                sprintf('Error occurred during export merge: %s', $exceptionMessage),
                ['exception' => $exception]
            );

        $this->messageProducer->expects($this->never())->method('send');

        $result = $this->postExportMessageProcessor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcess()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject */
        $message = $this->createMock(MessageInterface::class);
        $messageBody = [
            'jobId' => '1',
            'jobName' => 'job-name',
            'exportType' => 'type',
            'outputFormat' => 'csv',
            'email' => 'test@example.com',
            'notificationTemplate' => 'resultTemplate',
            'entity' => 'Acme',
        ];
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(json_encode($messageBody));

        $job = new Job();
        $childJob = new Job();
        $childJob->setRootJob($job);
        $job->setChildJobs([$childJob]);

        $this->jobRepository
            ->expects(self::once())
            ->method('findJobById')
            ->willReturn($childJob);

        $this->importExportResultSummarizer
            ->expects(self::any())
            ->method('processSummaryExportResultForNotification')
            ->willReturn([]);

        $this->exportHandler
            ->expects(self::once())
            ->method('exportResultFileMerge')
            ->willReturn('acme_filename');

        $this->messageProducer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    NotificationTopics::SEND_NOTIFICATION_EMAIL,
                    [
                        'sender' => [],
                        'toEmail' => 'test@example.com',
                        'body' => [],
                        'contentType' => 'text/html',
                        'template' => 'resultTemplate',
                    ]
                ],
                [
                    Topics::SAVE_IMPORT_EXPORT_RESULT,
                    ['jobId' => $job->getId(), 'type' => 'type', 'entity' => 'Acme']
                ]
            );

        $result = $this->postExportMessageProcessor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenRecipientUserIdGiven()
    {
        $jobId = 123;
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject */
        $message = $this->createMock(MessageInterface::class);
        $messageBody = [
            'jobId' => $jobId,
            'jobName' => 'job-name',
            'exportType' => 'type',
            'outputFormat' => 'csv',
            'email' => 'test@example.com',
            'recipientUserId' => self::USER_ID,
            'entity' => 'Acme',
        ];
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(json_encode($messageBody));

        /** @var Job|\PHPUnit\Framework\MockObject\MockObject */
        $job = $this->createMock(Job::class);
        $job->expects($this->any())
            ->method('getId')
            ->willReturn($jobId);

        $job->expects($this->any())
            ->method('getData')
            ->willReturn(['file' => 'file.csv']);

        $this->jobRepository
            ->expects(self::once())
            ->method('findJobById')
            ->willReturn($job);

        $job->expects(self::once())
            ->method('isRoot')
            ->willReturn(true);

        $job->expects(self::once())
            ->method('getChildJobs')
            ->willReturn([]);

        $fileName = 'filename.csv';
        $this->exportHandler
            ->expects(self::once())
            ->method('exportResultFileMerge')
            ->willReturn($fileName);

        $summary = ['template' => 'params'];
        $this->importExportResultSummarizer
            ->expects(self::once())
            ->method('processSummaryExportResultForNotification')
            ->with($job, $fileName)
            ->willReturn($summary);

        $this->messageProducer
            ->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    NotificationTopics::SEND_NOTIFICATION_EMAIL,
                    self::callback(function ($message) {
                        return !empty($message['recipientUserId']) && $message['recipientUserId'] === self::USER_ID;
                    })
                ],
                [
                    Topics::SAVE_IMPORT_EXPORT_RESULT,
                    ['jobId' => $job->getId(), 'type' => 'type', 'entity' => 'Acme']
                ]
            );

        self::assertSame(
            MessageProcessorInterface::ACK,
            $this->postExportMessageProcessor->process($message, $session)
        );
    }
}
