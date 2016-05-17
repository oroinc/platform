<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig;

use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use Oro\Component\Messaging\Transport\Session;
use Oro\Component\Messaging\ZeroConfig\ProducerInterface;
use Oro\Component\Messaging\ZeroConfig\Route;
use Oro\Component\Messaging\ZeroConfig\RouteRegistryInterface;
use Oro\Component\Messaging\ZeroConfig\RouterMessageProcessor;
use Oro\Component\Messaging\ZeroConfig\SessionInterface;

class RouterMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new RouterMessageProcessor($this->createSessionMock(), $this->createRouterRegistryMock(), '');
    }

    public function testShouldThrowExceptionIfMessageNameParameterIsNotSet()
    {
        $this->setExpectedException(\LogicException::class, 'Got message without "messageName" parameter');

        $processor = new RouterMessageProcessor($this->createSessionMock(), $this->createRouterRegistryMock(), '');
        $processor->process(new AmqpMessage(), $this->createTransportSessionMock());
    }

    public function testShouldSendRoutedMessageToQueue()
    {
        $route = new Route();
        $route->setMessageName('message-name');
        $route->setProcessorName('processor-name');

        $message = new AmqpMessage();
        $message->setBody('body');
        $message->setProperties([
            'messageName' => 'message-name',
        ]);

        $queueMessage = new AmqpMessage();

        $routeRegistry = $this->createRouterRegistryMock();
        $routeRegistry
            ->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue([$route]))
        ;

        $queueProducer = $this->createQueueProducerMock();
        $queueProducer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queueMessage))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('createQueueProducer')
            ->will($this->returnValue($queueProducer))
        ;
        $session
            ->expects($this->once())
            ->method('createMessage')
            ->will($this->returnValue($queueMessage))
        ;

        $processor = new RouterMessageProcessor($session, $routeRegistry, 'default-queue-name');
        $processor->process($message, $this->createTransportSessionMock());

        $expectedQueueMessageProperties = [
            'messageName' => 'message-name',
            'processorName' => 'processor-name',
            'queueName' => 'default-queue-name',
        ];

        $this->assertEquals($expectedQueueMessageProperties, $queueMessage->getProperties());
        $this->assertEquals('body', $queueMessage->getBody());
    }

    public function testShouldUseQueueNameFromRouteIfSetInsteadOfDefault()
    {
        $route = new Route();
        $route->setMessageName('message-name');
        $route->setProcessorName('processor-name');
        $route->setQueueName('non-default-queue-name');

        $message = new AmqpMessage();
        $message->setBody('body');
        $message->setProperties([
            'messageName' => 'message-name',
        ]);

        $queueMessage = new AmqpMessage();

        $routeRegistry = $this->createRouterRegistryMock();
        $routeRegistry
            ->expects($this->once())
            ->method('getRoutes')
            ->will($this->returnValue([$route]))
        ;

        $queueProducer = $this->createQueueProducerMock();
        $queueProducer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queueMessage))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('createQueueProducer')
            ->will($this->returnValue($queueProducer))
        ;
        $session
            ->expects($this->once())
            ->method('createMessage')
            ->will($this->returnValue($queueMessage))
        ;

        $processor = new RouterMessageProcessor($session, $routeRegistry, 'default-queue-name');
        $processor->process($message, $this->createTransportSessionMock());

        $expectedQueueMessageProperties = [
            'messageName' => 'message-name',
            'processorName' => 'processor-name',
            'queueName' => 'non-default-queue-name',
        ];

        $this->assertEquals($expectedQueueMessageProperties, $queueMessage->getProperties());
        $this->assertEquals('body', $queueMessage->getBody());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RouteRegistryInterface
     */
    protected function createRouterRegistryMock()
    {
        return $this->getMock(RouteRegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected function createTransportSessionMock()
    {
        return $this->getMock(Session::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    protected function createQueueProducerMock()
    {
        return $this->getMock(ProducerInterface::class);
    }
}
