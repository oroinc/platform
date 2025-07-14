<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggerExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface(): void
    {
        $this->assertClassImplements(ExtensionInterface::class, LoggerExtension::class);
    }

    public function testCouldBeConstructedWithLoggerAsFirstArgument(): void
    {
        new LoggerExtension($this->createMock(LoggerInterface::class));
    }

    public function testShouldSetLoggerToContextOnStart(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $extension = new LoggerExtension($logger);

        $context = new Context($this->createMock(SessionInterface::class));

        $extension->onStart($context);

        $this->assertSame($logger, $context->getLogger());
    }

    public function testShouldAddInfoMessageOnStart(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Set logger to the context');

        $extension = new LoggerExtension($logger);

        $context = new Context($this->createMock(SessionInterface::class));

        $extension->onStart($context);
    }
}
