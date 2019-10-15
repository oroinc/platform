<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Gaufrette\Filesystem;
use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessorAbstract;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ExportMessageProcessorAbstractTest extends TestCase
{
    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var JobStorage|MockObject */
    private $jobStorage;

    /** @var JobRunner|MockObject */
    private $jobRunner;

    /** @var ExportMessageProcessorAbstract|MockObject */
    private $processor;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->jobStorage = $this->createMock(JobStorage::class);
        $this->jobRunner = $this->createMock(JobRunner::class);

        $this->processor = $this->getMockBuilder(ExportMessageProcessorAbstract::class)
            ->setConstructorArgs([$this->jobRunner, $this->jobStorage, $this->logger])
            ->setMethods(['getSubscribedTopics', 'handleExport', 'getMessageBody'])
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
        $this->processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(false)
        ;

        $message = new NullMessage();

        $result = $this->processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessorAbstract::REJECT, $result);
    }

    public function runDelayedJobResultProvider()
    {
        return [
            [ true, ExportMessageProcessorAbstract::ACK ],
            [ false, ExportMessageProcessorAbstract::REJECT ],
        ];
    }

    /**
     * @dataProvider runDelayedJobResultProvider
     * @param string $jobResult
     * @param string $expectedResult
     */
    public function testShouldReturnMessageStatusDependsOfJobResult($jobResult, $expectedResult)
    {
        $this->jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with($this->equalTo(1))
            ->willReturn($jobResult)
        ;

        $this->processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(['jobId' => 1])
        ;

        $message = new NullMessage();

        $result = $this->processor->process($message, $this->createSessionMock());

        $this->assertEquals($expectedResult, $result);
    }

    public function testShouldHandleExportLogMessageAndSaveJobResult()
    {
        $exportResult = ['success' => true, 'readsCount' => 10, 'errorsCount' => 0];

        $job = new Job();

        $this->jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with($this->equalTo(1))
            ->will($this->returnCallback(function ($jobId, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            }))
        ;

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('Export result. Success: Yes. ReadsCount: 10. ErrorsCount: 0'))
        ;

        $this->jobStorage
            ->expects($this->once())
            ->method('saveJob')
        ;

        $this->processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(['jobId' => 1])
        ;
        $this->processor
            ->expects($this->once())
            ->method('handleExport')
            ->with($this->equalTo(['jobId' => 1]))
            ->willReturn($exportResult)
        ;

        $message = new NullMessage();

        $result = $this->processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessorAbstract::ACK, $result);
    }

    public function testShouldSaveJobResultWhenErrors(): void
    {
        $this->mockProcessor(
            $job = new Job(),
            $exportResult = [
                'success' => false,
                'readsCount' => 1,
                'errorsCount' => 1,
                'errors' => ['sample-error'],
            ]
        );

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Export result. Success: No. ReadsCount: 1. ErrorsCount: 1');

        $this->processor->setFileManager($fileManager = $this->createMock(FileManager::class));

        $fileManager
            ->expects($this->once())
            ->method('getFileSystem')
            ->willReturn($filesystem = $this->createMock(Filesystem::class));

        $filesystem
            ->expects($this->once())
            ->method('write')
            ->with($this->isType(IsType::TYPE_STRING), json_encode($exportResult['errors']));

        $result = $this->processor->process(new NullMessage(), $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessorAbstract::REJECT, $result);
        $this->assertArrayHasKey('errorLogFile', $job->getData());
    }

    /**
     * @param Job $job
     * @param array $exportResult
     *
     * @return void
     */
    private function mockProcessor(Job $job, array $exportResult): void
    {
        $this->jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with($jobId = 1, $this->isType(IsType::TYPE_CALLABLE))
            ->willReturnCallback(
                function ($jobId, $callback) use ($job) {
                    return $callback($this->jobRunner, $job);
                }
            );

        $this->jobStorage
            ->expects($this->once())
            ->method('saveJob')
            ->with($job, $this->isType(IsType::TYPE_CALLABLE))
            ->willReturnCallback(
                static function ($job, $callback) {
                    return $callback($job);
                }
            );

        $this->processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(['jobId' => $jobId]);

        $this->processor
            ->expects($this->once())
            ->method('handleExport')
            ->with(['jobId' => $jobId])
            ->willReturn($exportResult);
    }

    public function testShouldSaveJobResultWhenNoErrors(): void
    {
        $this->mockProcessor(
            $job = new Job(),
            $exportResult = [
                'success' => false,
                'readsCount' => 1,
                'errorsCount' => 0,
            ]
        );

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Export result. Success: No. ReadsCount: 1. ErrorsCount: 0');

        $this->processor->setFileManager($fileManager = $this->createMock(FileManager::class));

        $fileManager
            ->expects($this->never())
            ->method('getFileSystem');

        $result = $this->processor->process(new NullMessage(), $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessorAbstract::REJECT, $result);
        $this->assertArrayNotHasKey('errorLogFile', $job->getData());
    }

    /**
     * @return MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }
}
