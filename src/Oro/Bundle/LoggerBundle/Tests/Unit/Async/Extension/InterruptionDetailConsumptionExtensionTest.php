<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Async\Extension;

use Oro\Bundle\LoggerBundle\Async\Extension\InterruptionDetailConsumptionExtension;
use Oro\Bundle\LoggerBundle\Tests\Unit\Stub\ConfigManagerStub;
use Oro\Bundle\LoggerBundle\Tests\Unit\Stub\LoggerStub;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Log\MessageProcessorClassProvider;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InterruptionDetailConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|ContainerInterface */
    protected $container;

    /** @var MockObject|MessageProcessorClassProvider */
    protected $messageProcessorClassProvider;

    /** @var InterruptionDetailConsumptionExtension */
    protected $extension;

    protected function setUp(): void
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
        $messageProcessorClass = \get_class($messageProcessor);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessor($messageProcessor);
        $context->setMessage($message);

        $logger = $this->createMock(LoggerStub::class);
        $context->setLogger($logger);

        $this->messageProcessorClassProvider->expects(self::once())
            ->method('getMessageProcessorClass')
            ->with(self::identicalTo($messageProcessor), self::identicalTo($message))
            ->willReturn($messageProcessorClass);

        $this->extension->onPostReceived($context);

        $this->container->expects(static::exactly(2))
            ->method('get')
            ->willReturnMap([
                    ['oro_logger.cache', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $logger],
                    [
                        'oro_config.user',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $this->createMock(ConfigManagerStub::class)
                    ],
                ]);
        $logger->expects(static::once())
            ->method('info')
            ->with(
                \sprintf('The last processor executed before interrupt of consuming was "%s"', $messageProcessorClass)
            );
        $this->extension->onInterrupted($context);
    }

    public function testOnIdleShouldClearRememberedProcessor()
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $messageProcessorClass = get_class($messageProcessor);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessor($messageProcessor);
        $context->setMessage($message);

        $logger = $this->createMock(LoggerInterface::class);
        $context->setLogger($logger);

        $this->messageProcessorClassProvider->expects(self::once())
            ->method('getMessageProcessorClass')
            ->with(self::identicalTo($messageProcessor), self::identicalTo($message))
            ->willReturn($messageProcessorClass);

        $this->extension->onPostReceived($context);
        $this->extension->onIdle($context);

        $logger->expects(static::never())->method('info');
        $this->extension->onInterrupted($context);
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
        $logger = new LoggerStub();

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessor($messageProcessor);
        $context->setMessage($message);
        $context->setLogger($logger);

        $this->messageProcessorClassProvider->expects(self::once())
            ->method('getMessageProcessorClass')
            ->with(self::identicalTo($messageProcessor), self::identicalTo($message))
            ->willReturn($messageProcessorClass);

        $this->container->expects(static::exactly(2))
            ->method('get')
            ->willReturnMap([
                    ['oro_logger.cache', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $logger],
                    [
                        'oro_config.user',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $this->createMock(ConfigManagerStub::class)
                    ],
                ]);

        $this->extension->onPostReceived($context);
        $this->extension->onInterrupted($context);

        static::assertEquals(
            [
                \sprintf('The last processor executed before interrupt of consuming was "%s"', $messageProcessorClass)
            ],
            $logger->getMessages()
        );

        // verify that the last processor was cleared

        $logger = $this->createMock(LoggerInterface::class);
        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);
        $logger->expects(static::never())->method('info');
        $this->extension->onInterrupted($context);
    }
}
