<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig;

use Oro\Component\Messaging\Transport\Null\NullMessage;
use Oro\Component\Messaging\Transport\Session as TransportSession;
use Oro\Component\Messaging\ZeroConfig\Config;
use Oro\Component\Messaging\ZeroConfig\QueueProducer;
use Oro\Component\Messaging\ZeroConfig\Route;
use Oro\Component\Messaging\ZeroConfig\RouteRegistryInterface;
use Oro\Component\Messaging\ZeroConfig\RouterMessageProcessor;
use Oro\Component\Messaging\ZeroConfig\Session;

class RouterMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new RouterMessageProcessor($this->createSessionMock(), $this->createRouterRegistryMock());
    }

    public function testShouldThrowExceptionIfMessageNameParameterIsNotSet()
    {
        $this->setExpectedException(\LogicException::class, 'Got message without required parameter: "oro.messaging.zero_conf.topic_name"');

        $processor = new RouterMessageProcessor($this->createSessionMock(), $this->createRouterRegistryMock());
        $processor->process(new NullMessage(), $this->createTransportSessionMock());
    }

    public function testShouldSendRoutedMessageToQueue()
    {
        $route = new Route();
        $route->setTopicName('topic-name');
        $route->setProcessorName('processor-name');
        $route->setQueueName('queue-name');

        $message = new NullMessage();
        $message->setBody('body');
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => 'topic-name',
        ]);

        $queueMessage = new NullMessage();

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

        $processor = new RouterMessageProcessor($session, $routeRegistry);
        $processor->process($message, $this->createTransportSessionMock());

        $expectedQueueMessageProperties = [
            Config::PARAMETER_TOPIC_NAME => 'topic-name',
            Config::PARAMETER_PROCESSOR_NAME => 'processor-name',
            Config::PARAMETER_QUEUE_NAME => 'queue-name',
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
     * @return \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected function createSessionMock()
    {
        return $this->getMock(Session::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TransportSession
     */
    protected function createTransportSessionMock()
    {
        return $this->getMock(TransportSession::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueProducer
     */
    protected function createQueueProducerMock()
    {
        return $this->getMock(QueueProducer::class, [], [], '', false);
    }
}
