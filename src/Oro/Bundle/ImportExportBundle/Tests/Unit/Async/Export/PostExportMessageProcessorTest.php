<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\Export\PostExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobStorage;
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
     * @var JobStorage|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobStorage;

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

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->exportHandler = $this->createMock(ExportHandler::class);
        $this->messageProducer = $this->createMock(MessageProducer::class);
        $this->jobStorage = $this->createMock(JobStorage::class);
        $this->importExportResultSummarizer = $this->createMock(ImportExportResultSummarizer::class);
        $this->notificationSettings = $this->createMock(NotificationSettings::class);

        $this->postExportMessageProcessor = new PostExportMessageProcessor(
            $this->exportHandler,
            $this->messageProducer,
            $this->logger,
            $this->jobStorage,
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
            'notificationTemplate' => 'resultTemplate'
        ];
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(json_encode($messageBody));

        $job = new Job();
        $childJob = new Job();
        $childJob->setRootJob($job);
        $job->setChildJobs([$childJob]);

        $this->jobStorage
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
            'notificationTemplate' => 'resultTemplate'
        ];
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(json_encode($messageBody));

        $job = new Job();
        $childJob = new Job();
        $childJob->setRootJob($job);
        $job->setChildJobs([$childJob]);

        $this->jobStorage
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

        $this->messageProducer->expects($this->once())->method('send');

        $result = $this->postExportMessageProcessor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenRecipientUserIdGiven()
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
            'recipientUserId' => self::USER_ID
        ];
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(json_encode($messageBody));

        $job = $this->createMock(Job::class);
        $this->jobStorage
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
            ->expects(self::once())
            ->method('send')
            ->with(
                Topics::SEND_NOTIFICATION_EMAIL,
                self::callback(function ($message) {
                    return !empty($message['recipientUserId']) && $message['recipientUserId'] === self::USER_ID;
                })
            );

        self::assertSame(
            MessageProcessorInterface::ACK,
            $this->postExportMessageProcessor->process($message, $session)
        );
    }
}
