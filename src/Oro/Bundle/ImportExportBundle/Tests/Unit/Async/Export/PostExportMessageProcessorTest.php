<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Export\PostExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topic\SaveImportExportResultTopic;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
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

    private ImportExportResultSummarizer|\PHPUnit\Framework\MockObject\MockObject $importExportResultSummarizer;

    private JobRepository|\PHPUnit\Framework\MockObject\MockObject $jobRepository;

    private MessageProducer|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private ExportHandler|\PHPUnit\Framework\MockObject\MockObject $exportHandler;

    private PostExportMessageProcessor $postExportMessageProcessor;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->exportHandler = $this->createMock(ExportHandler::class);
        $this->messageProducer = $this->createMock(MessageProducer::class);
        $this->jobRepository = $this->createMock(JobRepository::class);
        $jobManager = $this->createMock(JobManagerInterface::class);
        $this->importExportResultSummarizer = $this->createMock(ImportExportResultSummarizer::class);
        $notificationSettings = $this->createMock(NotificationSettings::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->with(Job::class)
            ->willReturn($this->jobRepository);

        $this->postExportMessageProcessor = new PostExportMessageProcessor(
            $this->exportHandler,
            $this->messageProducer,
            $this->logger,
            $doctrineHelper,
            $jobManager,
            $this->importExportResultSummarizer,
            $notificationSettings
        );
    }

    public function testProcessExceptionsAreHandledDuringMerge(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $messageBody = [
            'jobId' => '1',
            'jobName' => 'job-name',
            'exportType' => 'type',
            'outputFormat' => 'csv',
            'notificationTemplate' => 'resultTemplate',
            'recipientUserId' => self::USER_ID,
            'entity' => 'Acme',
        ];
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($messageBody);

        $job = new Job();
        $childJob = new Job();
        $childJob->setRootJob($job);
        $job->setChildJobs([$childJob]);

        $this->jobRepository->expects(self::once())
            ->method('findJobById')
            ->willReturn($childJob);

        $this->importExportResultSummarizer->expects(self::any())
            ->method('processSummaryExportResultForNotification')
            ->willReturn([]);

        $this->exportHandler->expects(self::once())
            ->method('exportResultFileMerge')
            ->willReturn('acme_filename');

        $exceptionMessage = 'Exception message';
        $exception = new RuntimeException($exceptionMessage);
        $this->exportHandler->expects(self::once())
            ->method('exportResultFileMerge')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('critical')
            ->with(
                sprintf('Error occurred during export merge: %s', $exceptionMessage),
                ['exception' => $exception]
            );

        $this->messageProducer->expects(self::never())
            ->method('send');

        $result = $this->postExportMessageProcessor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcess(): void
    {
        $jobId = 123;
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $messageBody = [
            'jobId' => $jobId,
            'jobName' => 'job-name',
            'exportType' => 'type',
            'outputFormat' => 'csv',
            'recipientUserId' => self::USER_ID,
            'entity' => 'Acme',
        ];
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($messageBody);

        $job = $this->createMock(Job::class);
        $job->expects(self::any())
            ->method('getId')
            ->willReturn($jobId);

        $job->expects(self::any())
            ->method('getData')
            ->willReturn(['file' => 'file.csv']);

        $this->jobRepository->expects(self::once())
            ->method('findJobById')
            ->willReturn($job);

        $job->expects(self::once())
            ->method('isRoot')
            ->willReturn(true);

        $job->expects(self::once())
            ->method('getChildJobs')
            ->willReturn([]);

        $fileName = 'filename.csv';
        $this->exportHandler->expects(self::once())
            ->method('exportResultFileMerge')
            ->willReturn($fileName);

        $summary = ['template' => 'params'];
        $this->importExportResultSummarizer->expects(self::once())
            ->method('processSummaryExportResultForNotification')
            ->with($job, $fileName)
            ->willReturn($summary);

        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    SendEmailNotificationTemplateTopic::getName(),
                    self::callback(function ($message) {
                        return !empty($message['recipientUserId']) && $message['recipientUserId'] === self::USER_ID;
                    })
                ],
                [
                    SaveImportExportResultTopic::getName(),
                    ['jobId' => $job->getId(), 'type' => 'type', 'entity' => 'Acme']
                ]
            );

        self::assertSame(
            MessageProcessorInterface::ACK,
            $this->postExportMessageProcessor->process($message, $session)
        );
    }
}
