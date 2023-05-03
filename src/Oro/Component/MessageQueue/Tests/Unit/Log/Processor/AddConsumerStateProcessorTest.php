<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Processor;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Log\Converter\MessageToArrayConverterInterface;
use Oro\Component\MessageQueue\Log\Processor\AddConsumerStateProcessor;
use Oro\Component\MessageQueue\Tests\Unit\Log\Processor\Stub\ExtensionProxy;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\PhpUtils\Formatter\BytesFormatter;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AddConsumerStateProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** Simple test message */
    private const MESSAGE = ['message' => 'test', 'extra' => []];

    private ConsumerState $consumerState;
    private AddConsumerStateProcessor $processor;

    protected function setUp(): void
    {
        $this->consumerState = new ConsumerState();

        $messageToArrayConverter = $this->createMock(MessageToArrayConverterInterface::class);
        $messageToArrayConverter
            ->expects(self::any())
            ->method('convert')
            ->willReturnCallback(static fn (MessageInterface $message) => ['id' => $message->getMessageId()]);

        $this->processor = new AddConsumerStateProcessor($this->consumerState, $messageToArrayConverter);
    }

    private function getMessageMock(string $messageId): MessageInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getMessageId')
            ->willReturn($messageId);

        return $message;
    }

    public function testConsumerWasNotStarted(): void
    {
        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra' => [],
        ];
        self::assertEquals($expectedRecord['message'], $record['message']);
        self::assertSame($expectedRecord['extra'], $record['extra']);
    }

    public function testOnEmptyConsumerState(): void
    {
        $this->consumerState->startConsumption();

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra' => [],
        ];
        self::assertEquals($expectedRecord['message'], $record['message']);
        self::assertSame($expectedRecord['extra'], \array_diff_key($record['extra'], ['memory_usage' => 'x']));
    }

    public function testAddExtensionInfo(): void
    {
        $extension = $this->createMock(ExtensionInterface::class);

        $this->consumerState->startConsumption();
        $this->consumerState->setExtension($extension);

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra' => [
                'extension' => \get_class($extension),
            ],
        ];
        self::assertEquals($expectedRecord['message'], $record['message']);
        self::assertEquals($expectedRecord['extra']['extension'], $record['extra']['extension']);
    }

    public function testAddExtensionInfoForLazyService(): void
    {
        /** @var ExtensionInterface|\PHPUnit\Framework\MockObject\MockObject $extension */
        $extension = $this->createMock(ExtensionInterface::class);
        $extensionProxy = new ExtensionProxy($extension);

        $this->consumerState->startConsumption();
        $this->consumerState->setExtension($extensionProxy);

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra' => [
                'extension' => get_class($extension),
            ],
        ];
        self::assertEquals($expectedRecord['message'], $record['message']);
        self::assertEquals($expectedRecord['extra']['extension'], $record['extra']['extension']);
    }

    public function testAddMessageProcessorInfo(): void
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $messageProcessorClass = get_class($messageProcessor);

        $this->consumerState->startConsumption();
        $this->consumerState->setMessageProcessorClass($messageProcessorClass);
        $message = $this->getMessageMock('1');
        $this->consumerState->setMessage($message);

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra' => [
                'processor' => $messageProcessorClass,
                'message_id' => '1',
            ],
        ];
        self::assertEquals($expectedRecord['message'], $record['message']);
        self::assertEquals($expectedRecord['extra']['processor'], $record['extra']['processor']);
        self::assertEquals($expectedRecord['extra']['message_id'], $record['extra']['message_id']);
    }

    public function testAddMessageInfo(): void
    {
        $message = $this->getMessageMock('1');

        $this->consumerState->startConsumption();
        $this->consumerState->setMessage($message);

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra' => [
                'message_id' => '1',
            ],
        ];
        self::assertEquals($expectedRecord['message'], $record['message']);
        self::assertEquals($expectedRecord['extra']['message_id'], $record['extra']['message_id']);
    }

    public function testMoveMemoryUsageInfoFromContext(): void
    {
        $message = $this->getMessageMock('1');

        $this->consumerState->startConsumption();
        $this->consumerState->setMessage($message);

        $record = call_user_func($this->processor, [
            'message' => 'test',
            'extra' => [],
            'context' => ['peak_memory' => '10.0 MB', 'memory_taken' => '8.0 MB', 'test_memory' => '11.0 MB'],
        ]);

        $expectedRecord = [
            'message' => 'test',
            'extra' => [
                'message_id' => '1',
                'peak_memory' => '10.0 MB',
                'memory_taken' => '8.0 MB',
            ],
            'context' => [
                'test_memory' => '11.0 MB',
            ],
        ];
        self::assertEquals($expectedRecord['message'], $record['message']);
        self::assertEquals(
            $expectedRecord['extra'],
            \array_diff_key($record['extra'], ['memory_usage' => 'x', 'elapsed_time' => 'x'])
        );
        self::assertEquals($expectedRecord['context'], $record['context']);
    }

    public function testAddJobInfo(): void
    {
        $job = new Job();
        $job->setId(12);
        $job->setName('oro.test');
        $job->setData(['a' => 'b']);

        $this->consumerState->startConsumption();
        $this->consumerState->setJob($job);

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra' => [
                'job_id' => 12,
                'job_name' => 'oro.test',
                'job_data' => ['a' => 'b'],
            ],
        ];
        self::assertEquals($expectedRecord['message'], $record['message']);
        self::assertEquals($expectedRecord['extra'], \array_diff_key($record['extra'], ['memory_usage' => 'x']));
    }

    public function testMemoryUsage(): void
    {
        $this->consumerState->startConsumption();

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'extra' => [
                'memory_usage' => BytesFormatter::format($this->consumerState->getPeakMemory()),
            ],
        ];
        foreach ($expectedRecord as $key => $expectedValue) {
            self::assertEquals($expectedValue, $record[$key]);
        }
    }

    public function testElapsedTimeWithoutMessage(): void
    {
        $this->consumerState->startConsumption();

        $record = call_user_func($this->processor, self::MESSAGE);

        self::assertArrayNotHasKey('elapsed_time', $record['extra']);
    }

    public function testElapsedTime(): void
    {
        $this->consumerState->startConsumption();
        $this->consumerState->setMessage();

        $record = call_user_func($this->processor, self::MESSAGE);

        self::assertArrayHasKey('elapsed_time', $record['extra']);
        self::assertIsString($record['extra']['elapsed_time']);
        self::assertStringContainsString(' ms', $record['extra']['elapsed_time']);
    }
}
