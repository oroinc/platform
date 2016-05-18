<?php
namespace Oro\Component\Messaging\Tests\Consumption\Extension;

use Oro\Component\Messaging\Consumption\Context;
use Oro\Component\Messaging\Consumption\Extension;
use Oro\Component\Messaging\Consumption\Extension\LoggerExtension;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\LoggerInterface;

class LoggerExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(Extension::class, LoggerExtension::class);
    }

    public function testCouldBeConstructedWithLoggerAsFirstArgument()
    {
        new LoggerExtension($this->createLogger());
    }

    public function testShouldSetLoggerToContextOnStart()
    {
        $logger = $this->createLogger();

        $extension = new LoggerExtension($logger);

        $context = $this->createContextStub($logger);
        $context
            ->expects($this->once())
            ->method('setLogger')
            ->with($this->identicalTo($logger))
        ;

        $extension->onStart($context);
    }

    public function testShouldAddDebugMessageOnStart()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->at(0))
            ->method('debug')
            ->with($this->stringStartsWith('Set context\'s logger'))
        ;
        $logger
            ->expects($this->at(1))
            ->method('info')
            ->with('Start consuming')
        ;

        $extension = new LoggerExtension($logger);

        $context = $this->createContextStub($logger);

        $extension->onStart($context);
    }

    public function testShouldAddDebugMessageOnBeforeReceive()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Before receive')
        ;

        $extension = new LoggerExtension($logger);

        $context = $this->createContextStub($logger);

        $extension->onBeforeReceive($context);
    }

    public function testShouldAddDebugMessageOnPreReceived()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Message received')
        ;

        $extension = new LoggerExtension($logger);

        $context = $this->createContextStub($logger);

        $extension->onPreReceived($context);
    }

    public function testShouldAddDebugMessageOnPostReceived()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Message processed: ')
        ;

        $extension = new LoggerExtension($logger);

        $context = $this->createContextStub($logger);

        $extension->onPostReceived($context);
    }

    public function testShouldAddDebugMessageOnIdle()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Idle')
        ;

        $extension = new LoggerExtension($logger);

        $context = $this->createContextStub($logger);

        $extension->onIdle($context);
    }

    public function testShouldAddDebugMessageOnInterrupted()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Consuming interrupted')
        ;

        $extension = new LoggerExtension($logger);

        $context = $this->createContextStub($logger);

        $extension->onInterrupted($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    protected function createContextStub($logger)
    {
        $loggerMock = $this->getMock(Context::class, [], [], '', false);
        $loggerMock
            ->expects($this->any())
            ->method('getLogger')
            ->willReturn($logger)
        ;

        return $loggerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLogger()
    {
        return $this->getMock(LoggerInterface::class);
    }
}
