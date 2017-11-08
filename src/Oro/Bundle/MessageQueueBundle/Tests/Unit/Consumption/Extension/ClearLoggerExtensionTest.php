<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Psr\Log\LoggerInterface;

use Monolog\Logger;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\TestHandler;

use Symfony\Component\DependencyInjection\Container;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ClearLoggerExtension;

class ClearLoggerExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|Container */
    private $container;

    /** @var ClearLoggerExtension */
    private $extension;

    protected function setUp()
    {
        $this->container = $this->createMock(Container::class);

        $this->extension = new ClearLoggerExtension(
            $this->container,
            ['foo_loger']
        );
    }

    public function testShouldNotGetUninitializedLoggerFromContainer()
    {
        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_loger')
            ->willReturn(false);
        $this->container->expects(self::never())
            ->method('get');

        $this->extension->onPostReceived($this->createMock(Context::class));
    }

    public function testShouldSkipLoggerIfItIsNotInstanceOfLoggerClass()
    {
        $fooLogger = $this->createMock(LoggerInterface::class);

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_loger')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_loger')
            ->willReturn($fooLogger);

        $this->extension->onPostReceived($this->createMock(Context::class));
    }

    public function testShouldSkipHandlerIfItIsNotSupported()
    {
        $testHandler = $this->createMock(HandlerInterface::class);
        $fooLogger = $this->createMock(Logger::class);

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_loger')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_loger')
            ->willReturn($fooLogger);
        $fooLogger->expects(self::once())
            ->method('getHandlers')
            ->willReturn([$testHandler]);

        $this->extension->onPostReceived($this->createMock(Context::class));
    }

    public function testShouldRemoveAllRecordsFromFingersCrossedHandler()
    {
        $testHandler = $this->createMock(FingersCrossedHandler::class);
        $fooLogger = $this->createMock(Logger::class);

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_loger')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_loger')
            ->willReturn($fooLogger);
        $fooLogger->expects(self::once())
            ->method('getHandlers')
            ->willReturn([$testHandler]);
        $testHandler->expects(self::once())
            ->method('clear');

        $this->extension->onPostReceived($this->createMock(Context::class));
    }

    public function testShouldRemoveAllRecordsFromTestHandler()
    {
        $testHandler = $this->createMock(TestHandler::class);
        $fooLogger = $this->createMock(Logger::class);

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_loger')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_loger')
            ->willReturn($fooLogger);
        $fooLogger->expects(self::once())
            ->method('getHandlers')
            ->willReturn([$testHandler]);
        $testHandler->expects(self::once())
            ->method('clear');

        $this->extension->onPostReceived($this->createMock(Context::class));
    }
}
