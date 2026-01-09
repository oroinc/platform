<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Processor;

use Monolog\Level;
use Monolog\LogRecord;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Log\Converter\MessageToArrayConverterInterface;
use Oro\Component\MessageQueue\Log\Processor\AddConsumerStateProcessor;
use Oro\Component\MessageQueue\Tests\Unit\Log\Processor\Stub\ExtensionProxy;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\PhpUtils\Formatter\BytesFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AddConsumerStateProcessorTest extends TestCase
{
    private ConsumerState $consumerState;
    private AddConsumerStateProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->consumerState = new ConsumerState();

        $messageToArrayConverter = $this->createMock(MessageToArrayConverterInterface::class);
        $messageToArrayConverter->expects(self::any())
            ->method('convert')
            ->willReturnCallback(static fn (MessageInterface $message) => ['id' => $message->getMessageId()]);

        $this->processor = new AddConsumerStateProcessor($this->consumerState, $messageToArrayConverter);
    }

    private function getMessageMock(string $messageId): MessageInterface&MockObject
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getMessageId')
            ->willReturn($messageId);

        return $message;
    }

    private function createTestRecord(string $message = 'test', array $extra = [], array $context = []): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: $message,
            context: $context,
            extra: $extra
        );
    }

    public function testConsumerWasNotStarted(): void
    {
        $record = call_user_func($this->processor, $this->createTestRecord());

        self::assertEquals('test', $record['message']);
        self::assertSame([], $record['extra']);
    }

    public function testOnEmptyConsumerState(): void
    {
        $this->consumerState->startConsumption();

        $record = call_user_func($this->processor, $this->createTestRecord());

        self::assertEquals('test', $record['message']);
        self::assertSame([], \array_diff_key($record['extra'], ['memory_usage' => 'x']));
    }

    public function testAddExtensionInfo(): void
    {
        $extension = $this->createMock(ExtensionInterface::class);

        $this->consumerState->startConsumption();
        $this->consumerState->setExtension($extension);

        $record = call_user_func($this->processor, $this->createTestRecord());

        self::assertEquals('test', $record['message']);
        self::assertEquals(\get_class($extension), $record['extra']['extension']);
    }

    public function testAddExtensionInfoForLazyService(): void
    {
        $extension = $this->createMock(ExtensionInterface::class);
        $extensionProxy = new ExtensionProxy($extension);

        $this->consumerState->startConsumption();
        $this->consumerState->setExtension($extensionProxy);

        $record = call_user_func($this->processor, $this->createTestRecord());

        self::assertEquals('test', $record['message']);
        self::assertEquals(get_class($extension), $record['extra']['extension']);
    }

    public function testAddMessageProcessorInfo(): void
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $messageProcessorClass = get_class($messageProcessor);

        $this->consumerState->startConsumption();
        $this->consumerState->setMessageProcessorClass($messageProcessorClass);
        $message = $this->getMessageMock('1');
        $this->consumerState->setMessage($message);

        $record = call_user_func($this->processor, $this->createTestRecord());

        self::assertEquals('test', $record['message']);
        self::assertEquals($messageProcessorClass, $record['extra']['processor']);
        self::assertEquals('1', $record['extra']['message_id']);
    }

    public function testAddMessageInfo(): void
    {
        $message = $this->getMessageMock('1');

        $this->consumerState->startConsumption();
        $this->consumerState->setMessage($message);

        $record = call_user_func($this->processor, $this->createTestRecord());

        self::assertEquals('test', $record['message']);
        self::assertEquals('1', $record['extra']['message_id']);
    }

    public function testMoveMemoryUsageInfoFromContext(): void
    {
        $message = $this->getMessageMock('1');

        $this->consumerState->startConsumption();
        $this->consumerState->setMessage($message);

        $record = call_user_func(
            $this->processor,
            $this->createTestRecord(
                'test',
                [],
                ['peak_memory' => '10.0 MB', 'memory_taken' => '8.0 MB', 'test_memory' => '11.0 MB']
            )
        );

        self::assertEquals('test', $record['message']);
        self::assertEquals('1', $record['extra']['message_id']);
        self::assertEquals('10.0 MB', $record['extra']['peak_memory']);
        self::assertEquals('8.0 MB', $record['extra']['memory_taken']);
        self::assertEquals(['test_memory' => '11.0 MB'], $record['context']);
    }

    public function testAddJobInfo(): void
    {
        $job = new Job();
        $job->setId(12);
        $job->setName('oro.test');
        $job->setData(['a' => 'b']);

        $this->consumerState->startConsumption();
        $this->consumerState->setJob($job);

        $record = call_user_func($this->processor, $this->createTestRecord());

        self::assertEquals('test', $record['message']);
        self::assertEquals(12, $record['extra']['job_id']);
        self::assertEquals('oro.test', $record['extra']['job_name']);
        self::assertEquals(['a' => 'b'], $record['extra']['job_data']);
    }

    public function testMemoryUsage(): void
    {
        $this->consumerState->startConsumption();

        $record = call_user_func($this->processor, $this->createTestRecord());

        self::assertEquals(
            BytesFormatter::format($this->consumerState->getPeakMemory()),
            $record['extra']['memory_usage']
        );
    }

    public function testElapsedTimeWithoutMessage(): void
    {
        $this->consumerState->startConsumption();

        $record = call_user_func($this->processor, $this->createTestRecord());

        self::assertArrayNotHasKey('elapsed_time', $record['extra']);
    }

    public function testElapsedTime(): void
    {
        $this->consumerState->startConsumption();
        $this->consumerState->setMessage();

        $record = call_user_func($this->processor, $this->createTestRecord());

        self::assertArrayHasKey('elapsed_time', $record['extra']);
        self::assertIsString($record['extra']['elapsed_time']);
        self::assertStringContainsString(' ms', $record['extra']['elapsed_time']);
    }
}
