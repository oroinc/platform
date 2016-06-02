<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\LoggerInterface;

class LoggerExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, LoggerExtension::class);
    }

    public function testCouldBeConstructedWithLoggerAsFirstArgument()
    {
        new LoggerExtension($this->createLogger());
    }

    public function testShouldSetLoggerToContextOnStart()
    {
        $logger = $this->createLogger();

        $extension = new LoggerExtension($logger);

        $context = new Context($this->createSessionMock());

        $extension->onStart($context);

        $this->assertSame($logger, $context->getLogger());
    }

    public function testShouldAddInfoMessageOnStart()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringStartsWith('Set context\'s logger'))
        ;

        $extension = new LoggerExtension($logger);

        $context = new Context($this->createSessionMock());

        $extension->onStart($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLogger()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageConsumerInterface
     */
    protected function createMessageConsumerMock()
    {
        return $this->getMock(MessageConsumerInterface::class);
    }
}
