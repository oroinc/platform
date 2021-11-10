<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Async\Extension;

use Oro\Bundle\LoggerBundle\Async\Extension\InterruptionDetailConsumptionExtension;
use Oro\Bundle\LoggerBundle\Tests\Unit\Async\Extension\Stub\ConfigManagerProxyStub;
use Oro\Bundle\LoggerBundle\Tests\Unit\Async\Extension\Stub\LoggerProxyStub;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Log\MessageProcessorClassProvider;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InterruptionDetailConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    private ContainerInterface|\PHPUnit\Framework\MockObject\MockObject $container;

    private MessageProcessorClassProvider|\PHPUnit\Framework\MockObject\MockObject $messageProcessorClassProvider;

    private InterruptionDetailConsumptionExtension $extension;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->messageProcessorClassProvider = $this->createMock(MessageProcessorClassProvider::class);

        $this->extension = new InterruptionDetailConsumptionExtension(
            $this->container,
            $this->messageProcessorClassProvider
        );
    }

    public function testOnPostReceived(): void
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $messageProcessorClass = get_class($messageProcessor);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessorName($messageProcessorClass);
        $context->setMessage($message);

        $logger = $this->createMock(LoggerProxyStub::class);
        $context->setLogger($logger);

        $this->messageProcessorClassProvider->expects(self::once())
            ->method('getMessageProcessorClassByName')
            ->with(self::identicalTo($messageProcessorClass))
            ->willReturn($messageProcessorClass);

        $this->extension->onPostReceived($context);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_logger.cache', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $logger],
                    [
                        'oro_config.user',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $this->createMock(ConfigManagerProxyStub::class),
                    ],
                ]
            );
        $logger->expects(self::once())
            ->method('info')
            ->with(
                sprintf('The last processor executed before interrupt of consuming was "%s"', $messageProcessorClass)
            );

        $this->extension->onInterrupted($context);
    }

    public function testOnIdleShouldClearRememberedProcessor(): void
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $messageProcessorClass = get_class($messageProcessor);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessorName($messageProcessorClass);
        $context->setMessage($message);

        $logger = $this->createMock(LoggerInterface::class);
        $context->setLogger($logger);

        $this->messageProcessorClassProvider->expects(self::once())
            ->method('getMessageProcessorClassByName')
            ->with(self::identicalTo($messageProcessorClass))
            ->willReturn($messageProcessorClass);

        $this->extension->onPostReceived($context);
        $this->extension->onIdle($context);

        $logger->expects(self::never())
            ->method('info');

        $this->extension->onInterrupted($context);
    }

    public function testOnInterruptedWithoutLastProcessorClassName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);

        $logger->expects(self::never())
            ->method('info');

        $this->extension->onInterrupted($context);
    }

    public function testOnInterruptedWithLastProcessorClassName(): void
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $messageProcessorClass = get_class($messageProcessor);
        $logger = new LoggerProxyStub();

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessorName($messageProcessorClass);
        $context->setMessage($message);
        $context->setLogger($logger);

        $this->messageProcessorClassProvider->expects(self::once())
            ->method('getMessageProcessorClassByName')
            ->with(self::identicalTo($messageProcessorClass))
            ->willReturn($messageProcessorClass);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_logger.cache', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $logger],
                    [
                        'oro_config.user',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $this->createMock(ConfigManagerProxyStub::class),
                    ],
                ]
            );

        $this->extension->onPostReceived($context);
        $this->extension->onInterrupted($context);

        self::assertEquals(
            [
                [
                    'info',
                    sprintf(
                        'The last processor executed before interrupt of consuming was "%s"',
                        $messageProcessorClass
                    ),
                    [],
                ],
            ],
            $logger->cleanLogs()
        );

        // verify that the last processor was cleared
        $logger = $this->createMock(LoggerInterface::class);
        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);
        $logger->expects(self::never())
            ->method('info');

        $this->extension->onInterrupted($context);
    }
}
