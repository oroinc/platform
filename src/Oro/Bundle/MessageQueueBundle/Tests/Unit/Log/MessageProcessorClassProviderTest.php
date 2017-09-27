<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DelegateMessageProcessor;
use Oro\Component\MessageQueue\Client\MessageProcessorRegistryInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;

use Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Processor\Stub\MessageProcessorProxy;
use Oro\Bundle\MessageQueueBundle\Log\MessageProcessorClassProvider;

class MessageProcessorClassProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|MessageProcessorRegistryInterface */
    private $messageProcessorRegistry;

    /** @var MessageProcessorClassProvider */
    private $messageProcessorClassProvider;

    protected function setUp()
    {
        $this->messageProcessorRegistry = $this->createMock(MessageProcessorRegistryInterface::class);

        $this->messageProcessorClassProvider = new MessageProcessorClassProvider(
            $this->messageProcessorRegistry
        );
    }

    public function testGetMessageProcessorClassForLazyService()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $messageProcessorProxy = new MessageProcessorProxy($messageProcessor);

        $message = $this->createMock(MessageInterface::class);

        $this->assertEquals(
            get_class($messageProcessor),
            $this->messageProcessorClassProvider->getMessageProcessorClass(
                $messageProcessorProxy,
                $message
            )
        );
    }

    public function testGetMessageProcessorClassForDelegateMessageProcessor()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $messageProcessorDelegate = new DelegateMessageProcessor($this->messageProcessorRegistry);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getProperty')
            ->with(Config::PARAMETER_PROCESSOR_NAME)
            ->willReturn('test_processor');

        $this->messageProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('test_processor')
            ->willReturn($messageProcessor);

        $this->assertEquals(
            get_class($messageProcessor),
            $this->messageProcessorClassProvider->getMessageProcessorClass(
                $messageProcessorDelegate,
                $message
            )
        );
    }

    public function testGetMessageProcessorClassForDelegateMessageProcessorAndLazyService()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $messageProcessorProxy = new MessageProcessorProxy($messageProcessor);
        $messageProcessorDelegate = new DelegateMessageProcessor($this->messageProcessorRegistry);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getProperty')
            ->with(Config::PARAMETER_PROCESSOR_NAME)
            ->willReturn('test_processor');

        $this->messageProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('test_processor')
            ->willReturn($messageProcessorProxy);

        $this->assertEquals(
            get_class($messageProcessor),
            $this->messageProcessorClassProvider->getMessageProcessorClass(
                $messageProcessorDelegate,
                $message
            )
        );
    }

    public function testGetMessageProcessorClassForDelegateMessageProcessorAndMessageDoesNotHaveProcessorName()
    {
        $messageProcessorDelegate = new DelegateMessageProcessor($this->messageProcessorRegistry);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getProperty')
            ->with(Config::PARAMETER_PROCESSOR_NAME)
            ->willReturn(null);

        $this->messageProcessorRegistry->expects(self::never())
            ->method('get');

        $this->assertEquals(
            DelegateMessageProcessor::class,
            $this->messageProcessorClassProvider->getMessageProcessorClass(
                $messageProcessorDelegate,
                $message
            )
        );
    }

    public function testGetMessageProcessorClassForDelegateMessageProcessorAndUnknownProcessorName()
    {
        $messageProcessorDelegate = new DelegateMessageProcessor($this->messageProcessorRegistry);

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getProperty')
            ->with(Config::PARAMETER_PROCESSOR_NAME)
            ->willReturn('test_processor');

        $this->messageProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('test_processor')
            ->willThrowException(new \Exception('unknown processor'));

        $this->assertEquals(
            DelegateMessageProcessor::class,
            $this->messageProcessorClassProvider->getMessageProcessorClass(
                $messageProcessorDelegate,
                $message
            )
        );
    }
}
