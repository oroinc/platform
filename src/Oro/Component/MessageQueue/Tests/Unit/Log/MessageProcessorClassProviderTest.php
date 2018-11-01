<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DelegateMessageProcessor;
use Oro\Component\MessageQueue\Client\MessageProcessorRegistryInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Log\MessageProcessorClassProvider;
use Oro\Component\MessageQueue\Tests\Unit\Log\Processor\Stub\MessageProcessorProxy;
use Oro\Component\MessageQueue\Transport\MessageInterface;

class MessageProcessorClassProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|MessageProcessorRegistryInterface */
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
        $messageProcessorProxy = $this->createMock(MessageProcessorProxy::class);

        $messageProcessorProxy->expects(self::once())
            ->method('getWrappedValueHolderValue')
            ->willReturn($messageProcessor);

        $message = $this->createMock(MessageInterface::class);

        self::assertEquals(
            get_class($messageProcessor),
            $this->messageProcessorClassProvider->getMessageProcessorClass(
                $messageProcessorProxy,
                $message
            )
        );

        // test that the class name of the last processor is cached
        self::assertEquals(
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
        $message->expects(self::any())
            ->method('getProperty')
            ->with(Config::PARAMETER_PROCESSOR_NAME)
            ->willReturn('test_processor');

        $this->messageProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('test_processor')
            ->willReturn($messageProcessor);

        self::assertEquals(
            get_class($messageProcessor),
            $this->messageProcessorClassProvider->getMessageProcessorClass(
                $messageProcessorDelegate,
                $message
            )
        );

        // test that the class name of the last processor is cached
        self::assertEquals(
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

        self::assertEquals(
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

        self::assertEquals(
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

        self::assertEquals(
            DelegateMessageProcessor::class,
            $this->messageProcessorClassProvider->getMessageProcessorClass(
                $messageProcessorDelegate,
                $message
            )
        );
    }
}
