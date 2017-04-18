<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DelayedJobRunnerDecoratingProcessor;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DelayedJobRunnerDecoratingProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processor;

    /**
     * @var DelayedJobRunnerDecoratingProcessor
     */
    private $decoratorProcessor;

    protected function setUp()
    {
        $this->jobRunner = new JobRunner();
        $this->processor = $this->createMock(MessageProcessorInterface::class);

        $this->decoratorProcessor = new DelayedJobRunnerDecoratingProcessor($this->jobRunner, $this->processor);
    }

    /**
     * @dataProvider resultDataProvider
     * @param bool|string|null $processorResult
     * @param string $expected
     */
    public function testProcess($processorResult, $expected)
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode(['id' => 1, 'jobId' => 123]));

        $this->processor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf(MessageInterface::class))
            ->willReturn($processorResult);

        $this->assertEquals($expected, $this->decoratorProcessor->process($message, $session));
    }

    /**
     * @return array
     */
    public function resultDataProvider()
    {
        return [
            'ACK' => [MessageProcessorInterface::ACK, MessageProcessorInterface::ACK],
            'REJECT' => [MessageProcessorInterface::REJECT, MessageProcessorInterface::REJECT],
            'true' => [true, MessageProcessorInterface::ACK],
            'false' => [false, MessageProcessorInterface::REJECT],
            'null' => [null, MessageProcessorInterface::REJECT],
        ];
    }

    public function testProcessRequeue()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode(['jobId' => 123]));

        $this->processor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf(MessageInterface::class))
            ->willReturn(MessageProcessorInterface::REQUEUE);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('REQUEUE requested');
        $this->decoratorProcessor->process($message, $session);
    }

    public function testProcessWithoutJobId()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode(['id' => 1]));

        $this->processor->expects($this->never())
            ->method('process');

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->decoratorProcessor->process($message, $session));
    }
}
