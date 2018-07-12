<?php

namespace Oro\Bundle\LoggerBundle\Bundle\Tests\Unit\Async\Extension;

use Oro\Bundle\LoggerBundle\Async\Extension\InterruptionDetailConsumptionExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Log\MessageProcessorClassProvider;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InterruptionDetailConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    protected $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MessageProcessorClassProvider */
    protected $messageProcessorClassProvider;

    /** @var InterruptionDetailConsumptionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->messageProcessorClassProvider = $this->createMock(MessageProcessorClassProvider::class);

        $this->extension = new InterruptionDetailConsumptionExtension(
            $this->container,
            $this->messageProcessorClassProvider
        );
    }

    public function testOnPostReceived()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $messageProcessorClass = get_class($messageProcessor);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessor($messageProcessor);
        $context->setMessage($message);

        $this->messageProcessorClassProvider->expects(self::once())
            ->method('getMessageProcessorClass')
            ->with(self::identicalTo($messageProcessor), self::identicalTo($message))
            ->willReturn($messageProcessorClass);

        $this->extension->onPostReceived($context);

        self::assertAttributeEquals($messageProcessorClass, 'lastProcessorClassName', $this->extension);
    }

    public function testOnIdleShouldClearRememberedProcessor()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $messageProcessorClass = get_class($messageProcessor);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessor($messageProcessor);
        $context->setMessage($message);

        $this->messageProcessorClassProvider->expects(self::once())
            ->method('getMessageProcessorClass')
            ->with(self::identicalTo($messageProcessor), self::identicalTo($message))
            ->willReturn($messageProcessorClass);

        $this->extension->onPostReceived($context);
        $this->extension->onIdle($context);

        self::assertAttributeSame(null, 'lastProcessorClassName', $this->extension);
    }

    public function testOnInterruptedWithoutLastProcessorClassName()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);

        $logger->expects(self::never())
            ->method('info');

        $this->extension->onInterrupted($context);
    }

    public function testOnInterruptedWithLastProcessorClassName()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $messageProcessorClass = get_class($messageProcessor);
        $logger = $this->createMock(LoggerInterface::class);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessor($messageProcessor);
        $context->setMessage($message);
        $context->setLogger($logger);

        $this->messageProcessorClassProvider->expects(self::once())
            ->method('getMessageProcessorClass')
            ->with(self::identicalTo($messageProcessor), self::identicalTo($message))
            ->willReturn($messageProcessorClass);

        $this->container->expects(self::at(0))
            ->method('set')
            ->with('oro_logger.cache', null);
        $this->container->expects(self::at(1))
            ->method('set')
            ->with('oro_config.user', null);

        $logger->expects(self::once())
            ->method('info')
            ->with(sprintf(
                'The last processor executed before interrupt of consuming was "%s"',
                $messageProcessorClass
            ));

        $this->extension->onPostReceived($context);
        $this->extension->onInterrupted($context);

        self::assertAttributeSame(null, 'lastProcessorClassName', $this->extension);
    }
}
