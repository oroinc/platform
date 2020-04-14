<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Processor;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Log\Converter\MessageToArrayConverterInterface;
use Oro\Component\MessageQueue\Log\MessageProcessorClassProvider;
use Oro\Component\MessageQueue\Log\Processor\AddConsumerStateProcessor;
use Oro\Component\MessageQueue\Tests\Unit\Log\Processor\Stub\ExtensionProxy;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\PhpUtils\Formatter\BytesFormatter;

class AddConsumerStateProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** Simple test message */
    private const MESSAGE = ['message' => 'test', 'extra' => []];

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConsumerState */
    private $consumerState;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MessageProcessorClassProvider */
    private $messageProcessorClassProvider;

    /** @var AddConsumerStateProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->consumerState = new ConsumerState();

        $this->messageProcessorClassProvider = $this->createMock(MessageProcessorClassProvider::class);
        /** @var MessageToArrayConverterInterface|\PHPUnit\Framework\MockObject\MockObject $messageToArrayConverter */
        $messageToArrayConverter = $this->createMock(MessageToArrayConverterInterface::class);

        $messageToArrayConverter->expects(self::any())
            ->method('convert')
            ->willReturnCallback(function (MessageInterface $message) {
                return ['id' => $message->getMessageId()];
            });

        $this->processor = new AddConsumerStateProcessor(
            $this->consumerState,
            $this->messageProcessorClassProvider,
            $messageToArrayConverter
        );
    }

    /**
     * @param string $messageId
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageInterface
     */
    private function getMessageMock($messageId)
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getMessageId')
            ->willReturn($messageId);

        return $message;
    }

    public function testConsumerWasNotStarted()
    {
        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra'   => []
        ];
        $this->assertEquals($expectedRecord['message'], $record['message']);
        $this->assertSame($expectedRecord['extra'], $record['extra']);
    }

    public function testOnEmptyConsumerState()
    {
        $this->consumerState->startConsumption();

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra'   => []
        ];
        $this->assertEquals($expectedRecord['message'], $record['message']);
        $this->assertSame($expectedRecord['extra'], \array_diff_key($record['extra'], ['memory_usage' => 'x']));
    }

    public function testAddExtensionInfo()
    {
        $extension = $this->createMock(ExtensionInterface::class);

        $this->consumerState->startConsumption();
        $this->consumerState->setExtension($extension);

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra'   => [
                'extension' => \get_class($extension)
            ]
        ];
        $this->assertEquals($expectedRecord['message'], $record['message']);
        $this->assertEquals($expectedRecord['extra']['extension'], $record['extra']['extension']);
    }

    public function testAddExtensionInfoForLazyService()
    {
        /** @var ExtensionInterface|\PHPUnit\Framework\MockObject\MockObject $extension */
        $extension = $this->createMock(ExtensionInterface::class);
        $extensionProxy = new ExtensionProxy($extension);

        $this->consumerState->startConsumption();
        $this->consumerState->setExtension($extensionProxy);

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra'   => [
                'extension' => get_class($extension)
            ]
        ];
        $this->assertEquals($expectedRecord['message'], $record['message']);
        $this->assertEquals($expectedRecord['extra']['extension'], $record['extra']['extension']);
    }

    public function testAddMessageProcessorInfo()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $messageProcessorClass = get_class($messageProcessor);

        $this->consumerState->startConsumption();
        $this->consumerState->setMessageProcessor($messageProcessor);
        $message = $this->getMessageMock('1');
        $this->consumerState->setMessage($message);

        $this->messageProcessorClassProvider->expects(self::once())
            ->method('getMessageProcessorClass')
            ->with(self::identicalTo($messageProcessor), self::identicalTo($message))
            ->willReturn($messageProcessorClass);

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra'   => [
                'processor'  => $messageProcessorClass,
                'message_id' => '1'
            ]
        ];
        $this->assertEquals($expectedRecord['message'], $record['message']);
        $this->assertEquals($expectedRecord['extra']['processor'], $record['extra']['processor']);
        $this->assertEquals($expectedRecord['extra']['message_id'], $record['extra']['message_id']);
    }

    public function testAddMessageInfo()
    {
        $message = $this->getMessageMock('1');

        $this->consumerState->startConsumption();
        $this->consumerState->setMessage($message);

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'message' => 'test',
            'extra'   => [
                'message_id' => '1'
            ]
        ];
        $this->assertEquals($expectedRecord['message'], $record['message']);
        $this->assertEquals($expectedRecord['extra']['message_id'], $record['extra']['message_id']);
    }

    public function testMoveMemoryUsageInfoFromContext()
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
            'extra'   => [
                'message_id' => '1',
                'peak_memory' => '10.0 MB',
                'memory_taken' => '8.0 MB',
            ],
            'context' => [
                'test_memory' => '11.0 MB',
            ]
        ];
        $this->assertEquals($expectedRecord['message'], $record['message']);
        $this->assertEquals(
            $expectedRecord['extra'],
            \array_diff_key($record['extra'], ['memory_usage' => 'x', 'elapsed_time' => 'x'])
        );
        $this->assertEquals($expectedRecord['context'], $record['context']);
    }

    public function testAddJobInfo()
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
            'extra'   => [
                'job_id'   => 12,
                'job_name' => 'oro.test',
                'job_data' => ['a' => 'b']
            ]
        ];
        $this->assertEquals($expectedRecord['message'], $record['message']);
        $this->assertEquals($expectedRecord['extra'], \array_diff_key($record['extra'], ['memory_usage' => 'x']));
    }

    public function testMemoryUsage()
    {
        $this->consumerState->startConsumption();

        $record = call_user_func($this->processor, self::MESSAGE);

        $expectedRecord = [
            'extra' => [
                'memory_usage' => BytesFormatter::format($this->consumerState->getPeakMemory())
            ]
        ];
        foreach ($expectedRecord as $key => $expectedValue) {
            $this->assertEquals($expectedValue, $record[$key]);
        }
    }

    public function testElapsedTimeWithoutMessage()
    {
        $this->consumerState->startConsumption();

        $record = call_user_func($this->processor, self::MESSAGE);

        $this->assertArrayNotHasKey('elapsed_time', $record['extra']);
    }

    public function testElapsedTime()
    {
        $this->consumerState->startConsumption();
        $this->consumerState->setMessage();

        $record = call_user_func($this->processor, self::MESSAGE);

        $this->assertArrayHasKey('elapsed_time', $record['extra']);
        $this->assertIsString($record['extra']['elapsed_time']);
        static::assertStringContainsString(' ms', $record['extra']['elapsed_time']);
    }
}
