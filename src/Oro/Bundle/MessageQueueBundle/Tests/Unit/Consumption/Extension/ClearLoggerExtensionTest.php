<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Monolog\Handler\BufferHandler;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ClearLoggerExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;

class ClearLoggerExtensionTest extends TestCase
{
    private Container&MockObject $container;
    private ClearLoggerExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);

        $this->extension = new ClearLoggerExtension(
            $this->container,
            ['foo_loger']
        );
    }

    public function testShouldNotGetUninitializedLoggerFromContainer(): void
    {
        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->with('foo_loger')
            ->willReturn(false);
        $this->container->expects(self::never())
            ->method('get');

        $this->extension->onPostReceived($this->createMock(Context::class));
        $this->extension->onIdle($this->createMock(Context::class));
    }

    public function testShouldSkipLoggerIfItIsNotInstanceOfLoggerClass(): void
    {
        $fooLogger = $this->createMock(LoggerInterface::class);

        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->with('foo_loger')
            ->willReturn(true);
        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with('foo_loger')
            ->willReturn($fooLogger);

        $this->extension->onPostReceived($this->createMock(Context::class));
        $this->extension->onIdle($this->createMock(Context::class));
    }

    public function testShouldSkipHandlerIfItIsNotSupported(): void
    {
        $testHandler = $this->createMock(HandlerInterface::class);
        $fooLogger = $this->createMock(Logger::class);

        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->with('foo_loger')
            ->willReturn(true);
        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with('foo_loger')
            ->willReturn($fooLogger);
        $fooLogger->expects(self::exactly(2))
            ->method('getHandlers')
            ->willReturn([$testHandler]);
        $fooLogger->expects(self::exactly(2))
            ->method('reset');

        $this->extension->onPostReceived($this->createMock(Context::class));
        $this->extension->onIdle($this->createMock(Context::class));
    }

    public function testShouldRemoveAllRecordsFromFingersCrossedHandler(): void
    {
        $testHandler = $this->createMock(FingersCrossedHandler::class);
        $fooLogger = $this->createMock(Logger::class);

        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->with('foo_loger')
            ->willReturn(true);
        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with('foo_loger')
            ->willReturn($fooLogger);
        $fooLogger->expects(self::exactly(2))
            ->method('getHandlers')
            ->willReturn([$testHandler]);
        $testHandler->expects(self::exactly(2))
            ->method('clear');
        $fooLogger->expects(self::exactly(2))
            ->method('reset');

        $this->extension->onPostReceived($this->createMock(Context::class));
        $this->extension->onIdle($this->createMock(Context::class));
    }

    public function testShouldRemoveAllRecordsFromBufferHandler(): void
    {
        $testHandler = $this->createMock(BufferHandler::class);
        $fooLogger = $this->createMock(Logger::class);

        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->with('foo_loger')
            ->willReturn(true);
        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with('foo_loger')
            ->willReturn($fooLogger);
        $fooLogger->expects(self::exactly(2))
            ->method('getHandlers')
            ->willReturn([$testHandler]);
        $testHandler->expects(self::exactly(2))
            ->method('clear');
        $fooLogger->expects(self::exactly(2))
            ->method('reset');

        $this->extension->onPostReceived($this->createMock(Context::class));
        $this->extension->onIdle($this->createMock(Context::class));
    }

    public function testShouldRemoveAllRecordsFromTestHandler(): void
    {
        $testHandler = $this->createMock(TestHandler::class);
        $fooLogger = $this->createMock(Logger::class);

        $this->container->expects(self::exactly(2))
            ->method('initialized')
            ->with('foo_loger')
            ->willReturn(true);
        $this->container->expects(self::exactly(2))
            ->method('get')
            ->with('foo_loger')
            ->willReturn($fooLogger);
        $fooLogger->expects(self::exactly(2))
            ->method('getHandlers')
            ->willReturn([$testHandler]);
        $testHandler->expects(self::exactly(2))
            ->method('clear');
        $fooLogger->expects(self::exactly(2))
            ->method('reset');

        $this->extension->onPostReceived($this->createMock(Context::class));
        $this->extension->onIdle($this->createMock(Context::class));
    }
}
