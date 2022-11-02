<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessorAbstract;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use PHPUnit\Framework\Constraint\IsType;
use Psr\Log\LoggerInterface;

class ExportMessageProcessorAbstractTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var ExportMessageProcessorAbstract|\PHPUnit\Framework\MockObject\MockObject */
    private $processor;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->fileManager = $this->createMock(FileManager::class);

        $this->processor = $this->getMockBuilder(ExportMessageProcessorAbstract::class)
            ->setConstructorArgs([$this->jobRunner, $this->fileManager, $this->logger])
            ->onlyMethods(['getSubscribedTopics', 'handleExport', 'getMessageBody'])
            ->getMock();
    }

    public function testMustImplementMessageProcessorAndTopicSubscriberInterfaces()
    {
        $this->assertInstanceOf(MessageProcessorInterface::class, $this->processor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $this->processor);
    }

    public function testCanBeConstructedWithRequiredAttributes()
    {
        $this->assertInstanceOf(ExportMessageProcessorAbstract::class, $this->processor);
    }

    public function testShouldRejectMessageIfGetMessageBodyReturnFalse()
    {
        $this->processor->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(false);

        $message = new Message();

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function runDelayedJobResultProvider(): array
    {
        return [
            [ true, MessageProcessorInterface::ACK ],
            [ false, MessageProcessorInterface::REJECT ],
        ];
    }

    /**
     * @dataProvider runDelayedJobResultProvider
     */
    public function testShouldReturnMessageStatusDependsOfJobResult(bool $jobResult, string $expectedResult)
    {
        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(1)
            ->willReturn($jobResult);

        $this->processor->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(['jobId' => 1]);

        $message = new Message();

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals($expectedResult, $result);
    }

    public function testShouldHandleExportLogMessageAndSaveJobResult()
    {
        $exportResult = ['success' => true, 'readsCount' => 10, 'errorsCount' => 0];

        $job = new Job();

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(1)
            ->willReturnCallback(function ($jobId, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Export result. Success: Yes. ReadsCount: 10. ErrorsCount: 0');

        $this->processor->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(['jobId' => 1]);
        $this->processor->expects($this->once())
            ->method('handleExport')
            ->with(['jobId' => 1])
            ->willReturn($exportResult);

        $this->fileManager->expects($this->never())
            ->method('writeToStorage');

        $result = $this->processor->process(new Message(), $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        $this->assertArrayNotHasKey('errorLogFile', $job->getData());
    }

    public function testShouldSaveJobResultWhenErrors(): void
    {
        $this->processor = $this->getProcessor(
            $job = new Job(),
            $exportResult = [
                'success' => false,
                'readsCount' => 1,
                'errorsCount' => 1,
                'errors' => ['sample-error'],
            ]
        );

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Export result. Success: No. ReadsCount: 1. ErrorsCount: 1');

        $this->fileManager->expects($this->once())
            ->method('writeToStorage')
            ->with(JSON::encode($exportResult['errors']));

        $result = $this->processor->process(new Message(), $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
        $this->assertArrayHasKey('errorLogFile', $job->getData());
    }

    private function getProcessor(Job $job, array $exportResult): ExportMessageProcessorAbstract
    {
        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with($jobId = 1, $this->isType(IsType::TYPE_CALLABLE))
            ->willReturnCallback(function ($jobId, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        $this->processor->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(['jobId' => $jobId]);

        $this->processor->expects($this->once())
            ->method('handleExport')
            ->with(['jobId' => $jobId])
            ->willReturn($exportResult);

        return $this->processor;
    }
}
