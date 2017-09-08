<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Processor;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DelegateMessageProcessor;
use Oro\Component\MessageQueue\Client\MessageProcessorRegistryInterface;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;

use Oro\Bundle\MessageQueueBundle\Log\ConsumerState;
use Oro\Bundle\MessageQueueBundle\Log\Converter\MessageToArrayConverterInterface;
use Oro\Bundle\MessageQueueBundle\Log\Processor\AddConsumerStateProcessor;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Processor\Stub\ExtensionProxy;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Processor\Stub\MessageProcessorProxy;

class AddConsumerStateProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConsumerState */
    private $consumerState;

    /** @var \PHPUnit_Framework_MockObject_MockObject|MessageProcessorRegistryInterface */
    private $messageProcessorRegistry;

    /** @var AddConsumerStateProcessor */
    private $processor;

    protected function setUp()
    {
        $this->consumerState = new ConsumerState();
        $this->messageProcessorRegistry = $this->createMock(MessageProcessorRegistryInterface::class);
        $messageToArrayConverter = $this->createMock(MessageToArrayConverterInterface::class);

        $messageToArrayConverter->expects(self::any())
            ->method('convert')
            ->willReturnCallback(function (MessageInterface $message) {
                return ['id' => $message->getMessageId()];
            });

        $container = TestContainerBuilder::create()
            ->add('oro_message_queue.log.consumer_state', $this->consumerState)
            ->add('oro_message_queue.client.message_processor_registry', $this->messageProcessorRegistry)
            ->add('oro_message_queue.log.message_to_array_converter', $messageToArrayConverter)
            ->getContainer($this);

        $this->processor = new AddConsumerStateProcessor($container);
    }

    /**
     * @param string $messageId
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageInterface
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
        $this->assertEquals(
            ['message' => 'test', 'extra' => []],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testOnEmptyConsumerState()
    {
        $this->consumerState->startConsumption();

        $this->assertEquals(
            ['message' => 'test', 'extra' => []],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testAddExtensionInfo()
    {
        $extension = $this->createMock(ExtensionInterface::class);

        $this->consumerState->startConsumption();
        $this->consumerState->setExtension($extension);

        $this->assertEquals(
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
        $extension = $this->createMock(ExtensionInterface::class);
        $extensionProxy = new ExtensionProxy($extension);

        $this->consumerState->startConsumption();
        $this->consumerState->setExtension($extensionProxy);

        $this->assertEquals(
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

        $this->consumerState->startConsumption();
        $this->consumerState->setMessageProcessor($messageProcessor);
        $message = $this->getMessageMock('1');
        $this->consumerState->setMessage($message);

        $this->assertEquals(
            [
                'message' => 'test',
                'extra'   => [
                    'processor'  => get_class($messageProcessor),
                    'message_id' => '1'
                ]
            ],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testAddMessageProcessorInfoForLazyService()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $messageProcessorProxy = new MessageProcessorProxy($messageProcessor);

        $this->consumerState->startConsumption();
        $this->consumerState->setMessageProcessor($messageProcessorProxy);
        $message = $this->getMessageMock('1');
        $this->consumerState->setMessage($message);

        $this->assertEquals(
            [
                'message' => 'test',
                'extra'   => [
                    'processor'  => get_class($messageProcessor),
                    'message_id' => '1'
                ]
            ],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testAddMessageProcessorInfoForDelegateMessageProcessor()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $messageProcessorDelegate = new DelegateMessageProcessor($this->messageProcessorRegistry);

        $this->consumerState->startConsumption();
        $this->consumerState->setMessageProcessor($messageProcessorDelegate);
        $message = $this->getMessageMock('1');
        $message->expects(self::once())
            ->method('getProperty')
            ->with(Config::PARAMETER_PROCESSOR_NAME)
            ->willReturn('test_processor');
        $this->consumerState->setMessage($message);

        $this->messageProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('test_processor')
            ->willReturn($messageProcessor);

        $this->assertEquals(
            [
                'message' => 'test',
                'extra'   => [
                    'processor'  => get_class($messageProcessor),
                    'message_id' => '1'
                ]
            ],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testAddMessageProcessorInfoForDelegateMessageProcessorAndLazyService()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $messageProcessorProxy = new MessageProcessorProxy($messageProcessor);
        $messageProcessorDelegate = new DelegateMessageProcessor($this->messageProcessorRegistry);

        $this->consumerState->startConsumption();
        $this->consumerState->setMessageProcessor($messageProcessorDelegate);
        $message = $this->getMessageMock('1');
        $message->expects(self::once())
            ->method('getProperty')
            ->with(Config::PARAMETER_PROCESSOR_NAME)
            ->willReturn('test_processor');
        $this->consumerState->setMessage($message);

        $this->messageProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('test_processor')
            ->willReturn($messageProcessorProxy);

        $this->assertEquals(
            [
                'message' => 'test',
                'extra'   => [
                    'processor'  => get_class($messageProcessor),
                    'message_id' => '1'
                ]
            ],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testAddMessageProcessorInfoForDelegateMessageProcessorAndMessageDoesNotHaveProcessorName()
    {
        $messageProcessorDelegate = new DelegateMessageProcessor($this->messageProcessorRegistry);

        $this->consumerState->startConsumption();
        $this->consumerState->setMessageProcessor($messageProcessorDelegate);
        $message = $this->getMessageMock('1');
        $message->expects(self::once())
            ->method('getProperty')
            ->with(Config::PARAMETER_PROCESSOR_NAME)
            ->willReturn(null);
        $this->consumerState->setMessage($message);

        $this->messageProcessorRegistry->expects(self::never())
            ->method('get');

        $this->assertEquals(
            [
                'message' => 'test',
                'extra'   => [
                    'processor'  => DelegateMessageProcessor::class,
                    'message_id' => '1'
                ]
            ],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
        );
    }

    public function testAddMessageProcessorInfoForDelegateMessageProcessorAndUnknownProcessorName()
    {
        $messageProcessorDelegate = new DelegateMessageProcessor($this->messageProcessorRegistry);

        $this->consumerState->startConsumption();
        $this->consumerState->setMessageProcessor($messageProcessorDelegate);
        $message = $this->getMessageMock('1');
        $message->expects(self::once())
            ->method('getProperty')
            ->with(Config::PARAMETER_PROCESSOR_NAME)
            ->willReturn('test_processor');
        $this->consumerState->setMessage($message);

        $this->messageProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('test_processor')
            ->willThrowException(new \Exception('unknown processor'));

        $this->assertEquals(
            [
                'message' => 'test',
                'extra'   => [
                    'processor'  => DelegateMessageProcessor::class,
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

        $this->assertEquals(
            [
                'message' => 'test',
                'extra'   => [
                    'message_id' => '1'
                ]
            ],
            call_user_func($this->processor, ['message' => 'test', 'extra' => []])
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

        $this->assertEquals(
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
}
