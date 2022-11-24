<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DelayedJobRunnerDecoratingProcessor;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DelayedJobRunnerDecoratingProcessorTest extends \PHPUnit\Framework\TestCase
{
    private MessageProcessorInterface|\PHPUnit\Framework\MockObject\MockObject $processor;

    private DelayedJobRunnerDecoratingProcessor $decoratorProcessor;

    protected function setUp(): void
    {
        $jobRunner = new JobRunner();
        $this->processor = $this->createMock(MessageProcessorInterface::class);

        $this->decoratorProcessor = new DelayedJobRunnerDecoratingProcessor($jobRunner, $this->processor);
    }

    /**
     * @dataProvider resultDataProvider
     */
    public function testProcess(string|bool|null $processorResult, string $expected): void
    {
        $message = $this->createMock(MessageInterface::class);
        $session = $this->createMock(SessionInterface::class);

        $message->expects(self::any())
            ->method('getBody')
            ->willReturn(['id' => 1, 'jobId' => 123]);

        $this->processor->expects(self::once())
            ->method('process')
            ->with(self::isInstanceOf(MessageInterface::class))
            ->willReturn($processorResult);

        self::assertEquals($expected, $this->decoratorProcessor->process($message, $session));
    }

    public function resultDataProvider(): array
    {
        return [
            'ACK' => [MessageProcessorInterface::ACK, MessageProcessorInterface::ACK],
            'REJECT' => [MessageProcessorInterface::REJECT, MessageProcessorInterface::REJECT],
            'true' => [true, MessageProcessorInterface::ACK],
            'false' => [false, MessageProcessorInterface::REJECT],
            'null' => [null, MessageProcessorInterface::REJECT],
        ];
    }

    public function testProcessRequeue(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $session = $this->createMock(SessionInterface::class);

        $message->expects(self::any())
            ->method('getBody')
            ->willReturn(['jobId' => 123]);

        $this->processor->expects(self::once())
            ->method('process')
            ->with(self::isInstanceOf(MessageInterface::class))
            ->willReturn(MessageProcessorInterface::REQUEUE);

        self::assertEquals(MessageProcessorInterface::REQUEUE, $this->decoratorProcessor->process($message, $session));
    }

    public function testProcessWithoutJobId(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $session = $this->createMock(SessionInterface::class);

        $message->expects(self::any())
            ->method('getBody')
            ->willReturn(['id' => 1]);

        $this->processor->expects(self::never())
            ->method('process');

        self::assertEquals(MessageProcessorInterface::REJECT, $this->decoratorProcessor->process($message, $session));
    }
}
