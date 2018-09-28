<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Async\Export\PostExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class PostExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    private const USER_ID = 132;

    /**
     * @var PostExportMessageProcessor
     */
    private $postExportMessageProcessor;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var ImportExportResultSummarizer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $importExportResultSummarizer;

    /**
     * @var JobStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jobStorage;

    /**
     * @var MessageProducer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageProducer;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var ExportHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $exportHandler;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->exportHandler = $this->createMock(ExportHandler::class);
        $this->messageProducer = $this->createMock(MessageProducer::class);
        $this->jobStorage = $this->createMock(JobStorage::class);
        $this->importExportResultSummarizer = $this->createMock(ImportExportResultSummarizer::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->postExportMessageProcessor = new PostExportMessageProcessor(
            $this->exportHandler,
            $this->messageProducer,
            $this->logger,
            $this->jobStorage,
            $this->importExportResultSummarizer,
            $this->configManager
        );
    }

    public function testProcessExceptionsAreHandledDuringMerge()
    {
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
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
        $this->configManager->expects($this->never())->method('get');

        $result = $this->postExportMessageProcessor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcess()
    {
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
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

        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                'oro_notification.email_notification_sender_email',
                'oro_notification.email_notification_sender_name'
            );

        $result = $this->postExportMessageProcessor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenRecipientUserIdGiven()
    {
        $session = $this->createMock(SessionInterface::class);
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
