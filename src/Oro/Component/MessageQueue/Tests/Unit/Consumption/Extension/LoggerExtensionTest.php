<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
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

        $queue = new NullQueue('aQueueName');

        $messageConsumerMock = $this->createMessageConsumerMock();
        $messageConsumerMock
            ->expects($this->once())
            ->method('getQueue')
            ->willReturn($queue)
        ;

        $context = $this->createContextStub($logger);
        $context
            ->expects($this->once())
            ->method('setLogger')
            ->with($this->identicalTo($logger))
        ;
        $context
            ->expects($this->any())
            ->method('getMessageConsumer')
            ->willReturn($messageConsumerMock)
        ;

        $extension->onStart($context);
    }

    public function testShouldAddInfoMessageOnStart()
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
            ->with('Start consuming from queue aQueueName')
        ;

        $queue = new NullQueue('aQueueName');

        $messageConsumerMock = $this->createMessageConsumerMock();
        $messageConsumerMock
            ->expects($this->once())
            ->method('getQueue')
            ->willReturn($queue)
        ;

        $extension = new LoggerExtension($logger);

        $context = $this->createContextStub($logger);
        $context
            ->expects($this->once())
            ->method('getMessageConsumer')
            ->willReturn($messageConsumerMock)
        ;

        $extension->onStart($context);
    }

    public function testShouldAddInfoMessageOnBeforeReceive()
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

    public function testShouldAddInfoMessageOnPreReceived()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Message received')
        ;

        $extension = new LoggerExtension($logger);

        $context = $this->createContextStub($logger, new NullMessage());

        $extension->onPreReceived($context);
    }

    public function testShouldAddDebugInfoAboutMessageOnPreReceived()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['theFoo' => 'theFooVal']);
        $message->setProperties(['theBar' => 'theBarVal']);

        $logger = $this->createLogger();
        $logger
            ->expects($this->at(1))
            ->method('debug')
            ->with($this->stringStartsWith('Headers: array ('))
        ;
        $logger
            ->expects($this->at(2))
            ->method('debug')
            ->with($this->stringStartsWith('Properties: array ('))
        ;
        $logger
            ->expects($this->at(3))
            ->method('debug')
            ->with($this->stringStartsWith('Payload: \'theBody\''))
        ;

        $extension = new LoggerExtension($logger);

        $context = $this->createContextStub($logger, $message);

        $extension->onPreReceived($context);
    }

    public function testShouldAddInfoMessageOnPostReceived()
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

    public function testShouldAddInfoMessageOnIdle()
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

    public function testShouldAddInfoMessageOnInterrupted()
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
    protected function createContextStub($logger = null, $message = null)
    {
        $contextMock = $this->getMock(Context::class, [], [], '', false);
        $contextMock
            ->expects($this->any())
            ->method('getLogger')
            ->willReturn($logger)
        ;
        $contextMock
            ->expects($this->any())
            ->method('getMessage')
            ->willReturn($message)
        ;

        return $contextMock;
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
