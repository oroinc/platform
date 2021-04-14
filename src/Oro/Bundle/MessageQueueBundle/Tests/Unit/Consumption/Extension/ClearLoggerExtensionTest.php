<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Monolog\Handler\BufferHandler;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ClearLoggerExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;

class ClearLoggerExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|Container */
    private $container;

    /** @var ClearLoggerExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);

        $this->extension = new ClearLoggerExtension(
            $this->container,
            ['foo_loger']
        );
    }

    public function testShouldNotGetUninitializedLoggerFromContainer()
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

    public function testShouldSkipLoggerIfItIsNotInstanceOfLoggerClass()
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

    public function testShouldSkipHandlerIfItIsNotSupported()
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

    public function testShouldRemoveAllRecordsFromFingersCrossedHandler()
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

    public function testShouldRemoveAllRecordsFromBufferHandler()
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

    public function testShouldRemoveAllRecordsFromTestHandler()
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
