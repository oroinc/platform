<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Processor;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;

use Oro\Bundle\MessageQueueBundle\Log\ConsumerState;
use Oro\Bundle\MessageQueueBundle\Log\Converter\MessageToArrayConverterInterface;
use Oro\Bundle\MessageQueueBundle\Log\MessageProcessorClassProvider;
use Oro\Bundle\MessageQueueBundle\Log\Processor\AddConsumerStateProcessor;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Processor\Stub\ExtensionProxy;

class AddConsumerStateProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConsumerState */
    private $consumerState;

    /** @var \PHPUnit_Framework_MockObject_MockObject|MessageProcessorClassProvider */
    private $messageProcessorClassProvider;

    /** @var AddConsumerStateProcessor */
    private $processor;

    protected function setUp()
    {
        $this->consumerState = new ConsumerState();
        $this->messageProcessorClassProvider = $this->createMock(MessageProcessorClassProvider::class);
        $messageToArrayConverter = $this->createMock(MessageToArrayConverterInterface::class);

        $messageToArrayConverter->expects(self::any())
            ->method('convert')
            ->willReturnCallback(function (MessageInterface $message) {
                return ['id' => $message->getMessageId()];
            });

        $container = TestContainerBuilder::create()
            ->add('oro_message_queue.log.consumer_state', $this->consumerState)
            ->add('oro_message_queue.log.message_processor_class_provider', $this->messageProcessorClassProvider)
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
        $messageProcessorClass = get_class($messageProcessor);

        $this->consumerState->startConsumption();
        $this->consumerState->setMessageProcessor($messageProcessor);
        $message = $this->getMessageMock('1');
        $this->consumerState->setMessage($message);

        $this->messageProcessorClassProvider->expects(self::once())
            ->method('getMessageProcessorClass')
            ->with(self::identicalTo($messageProcessor), self::identicalTo($message))
            ->willReturn($messageProcessorClass);

        $this->assertEquals(
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
