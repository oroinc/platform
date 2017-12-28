<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Async\Export\PostExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class PostExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
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
        $session = $this->createMock(SessionInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $messageBody = [
            'jobId' => '1',
            'jobName' => 'job-name',
            'exportType' => 'type',
            'outputFormat' => 'csv',
            'email' => 'test@example.com',
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

        $this->importExportResultSummarizer
            ->expects(self::never())
            ->method('processSummaryExportResultForNotification');

        $result = $this->postExportMessageProcessor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }
}
