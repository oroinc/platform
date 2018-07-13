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
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConsumerState */
    private $consumerState;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MessageProcessorClassProvider */
    private $messageProcessorClassProvider;

    /** @var AddConsumerStateProcessor */
    private $processor;

    protected function setUp()
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
        $this->assertArraySubset(
            ['message' => 'test', 'extra' => []],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testOnEmptyConsumerState()
    {
        $this->consumerState->startConsumption();

        $this->assertArraySubset(
            ['message' => 'test', 'extra' => []],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testAddExtensionInfo()
    {
        $extension = $this->createMock(ExtensionInterface::class);

        $this->consumerState->startConsumption();
        $this->consumerState->setExtension($extension);

        $this->assertArraySubset(
            [
                'message' => 'test',
                'extra'   => [
                    'extension' => get_class($extension)
                ]
            ],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testAddExtensionInfoForLazyService()
    {
        /** @var ExtensionInterface|\PHPUnit\Framework\MockObject\MockObject $extension */
        $extension = $this->createMock(ExtensionInterface::class);
        $extensionProxy = new ExtensionProxy($extension);

        $this->consumerState->startConsumption();
        $this->consumerState->setExtension($extensionProxy);

        $this->assertArraySubset(
            [
                'message' => 'test',
                'extra'   => [
                    'extension' => get_class($extension)
                ]
            ],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
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

        $this->assertArraySubset(
            [
                'message' => 'test',
                'extra'   => [
                    'processor'  => $messageProcessorClass,
                    'message_id' => '1'
                ]
            ],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testAddMessageInfo()
    {
        $message = $this->getMessageMock('1');

        $this->consumerState->startConsumption();
        $this->consumerState->setMessage($message);

        $this->assertArraySubset(
            [
                'message' => 'test',
                'extra'   => [
                    'message_id' => '1'
                ]
            ],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testMoveMemoryUsageInfoFromContext()
    {
        $message = $this->getMessageMock('1');

        $this->consumerState->startConsumption();
        $this->consumerState->setMessage($message);

        $this->assertArraySubset(
            [
                'message' => 'test',
                'extra'   => [
                    'message_id' => '1',
                    'peak_memory' => '10.0 MB',
                    'memory_taken' => '8.0 MB',
                ],
                'context' => [
                    'test_memory' => '11.0 MB',
                ]
            ],
            call_user_func($this->processor, [
                'message' => 'test',
                'extra' => [],
                'context' => ['peak_memory' => '10.0 MB', 'memory_taken' => '8.0 MB', 'test_memory' => '11.0 MB'],
            ])
        );
    }

    public function testAddJobInfo()
    {
        $job = new Job();
        $job->setId(12);
        $job->setName('oro.test');
        $job->setData(['a' => 'b']);

        $this->consumerState->startConsumption();
        $this->consumerState->setJob($job);

        $this->assertArraySubset(
            [
                'message' => 'test',
                'extra'   => [
                    'job_id'   => 12,
                    'job_name' => 'oro.test',
                    'job_data' => ['a' => 'b']
                ]
            ],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testMemoryUsage()
    {
        $this->consumerState->startConsumption();

        $record = call_user_func($this->processor, ['message' => 'test', 'extra' => []]);

        $this->assertArraySubset(
            [
                'extra' => [
                    'memory_usage' => BytesFormatter::format($this->consumerState->getPeakMemory())
                ]
            ],
            $record
        );
    }

    public function testElapsedTimeWithoutMessage()
    {
        $this->consumerState->startConsumption();

        $record = call_user_func($this->processor, ['message' => 'test', 'extra' => []]);

        $this->assertArrayNotHasKey('elapsed_time', $record['extra']);
    }

    public function testElapsedTime()
    {
        $this->consumerState->startConsumption();
        $this->consumerState->setMessage();

        $record = call_user_func($this->processor, ['message' => 'test', 'extra' => []]);

        $this->assertArrayHasKey('elapsed_time', $record['extra']);
        $this->assertInternalType('string', $record['extra']['elapsed_time']);
        $this->assertContains(' ms', $record['extra']['elapsed_time']);
    }
}
