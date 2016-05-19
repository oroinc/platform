<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig\Amqp;

use Oro\Component\Messaging\Transport\MessageProducer;
use Oro\Component\Messaging\Transport\Null\NullMessage;
use Oro\Component\Messaging\Transport\Null\NullQueue;
use Oro\Component\Messaging\Transport\Null\NullTopic;
use Oro\Component\Messaging\Transport\Session as TransportSession;
use Oro\Component\Messaging\ZeroConfig\QueueProducer;
use Oro\Component\Messaging\ZeroConfig\Config;
use Oro\Component\Messaging\ZeroConfig\Session;

class QueueProducerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new QueueProducer($this->createSessionMock(), new Config('', '', '', '', ''));
    }

    public function testThrowExceptionIfProcessorNameParameterIsNotSet()
    {
        $this->setExpectedException(\LogicException::class, 'Got message without required parameter: "oro.messaging.zero_conf.processor_name"');

        $producer = new QueueProducer($this->createSessionMock(), new Config('', '', '', '', ''));
        $producer->send(new NullMessage());
    }

    public function testShouldSendMessageAndCreateSchema()
    {
        $topic = new NullTopic('topic');
        $queue = new NullQueue('queue');

        $message = new NullMessage();
        $message->setBody('body');
        $message->setProperties([
            Config::PARAMETER_PROCESSOR_NAME => 'processor-name',
        ]);

        $messageProducer = $this->createMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($message))
        ;

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue($messageProducer))
        ;
        $transportSession
            ->expects($this->once())
            ->method('declareTopic')
            ->with($this->identicalTo($topic))
        ;
        $transportSession
            ->expects($this->once())
            ->method('declareQueue')
            ->with($this->identicalTo($queue))
        ;
        $transportSession
            ->expects($this->once())
            ->method('declareBind')
            ->with($this->identicalTo($topic), $this->identicalTo($queue))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('getTransportSession')
            ->will($this->returnValue($transportSession))
        ;
        $session
            ->expects($this->once())
            ->method('createQueueTopic')
            ->with('default-queue-name')
            ->will($this->returnValue($topic))
        ;
        $session
            ->expects($this->once())
            ->method('createQueueQueue')
            ->with('default-queue-name')
            ->will($this->returnValue($queue))
        ;

        $producer = new QueueProducer($session, new Config('', '', '', '', 'default-queue-name'));
        $producer->send($message);
    }

    public function testShouldUseDefaultQueueNameIfNotSetInMessage()
    {
        $topic = new NullTopic('topic');
        $queue = new NullQueue('queue');

        $message = new NullMessage();
        $message->setProperties([
            Config::PARAMETER_PROCESSOR_NAME => 'processor-name',
        ]);

        $messageProducer = $this->createMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($message))
        ;

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue($messageProducer))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('getTransportSession')
            ->will($this->returnValue($transportSession))
        ;
        $session
            ->expects($this->once())
            ->method('createQueueTopic')
            ->with('default-queue-name')
            ->will($this->returnValue($topic))
        ;
        $session
            ->expects($this->once())
            ->method('createQueueQueue')
            ->with('default-queue-name')
            ->will($this->returnValue($queue))
        ;

        $producer = new QueueProducer($session, new Config('', '', '', '', 'default-queue-name'));
        $producer->send($message);
    }

    public function testShouldUseQueueNameFromMessageIfSet()
    {
        $topic = new NullTopic('topic');
        $queue = new NullQueue('queue');

        $message = new NullMessage();
        $message->setProperties([
            Config::PARAMETER_PROCESSOR_NAME => 'processor-name',
            Config::PARAMETER_QUEUE_NAME => 'queue-name',
        ]);

        $messageProducer = $this->createMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($message))
        ;

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue($messageProducer))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('getTransportSession')
            ->will($this->returnValue($transportSession))
        ;
        $session
            ->expects($this->once())
            ->method('createQueueTopic')
            ->with('queue-name')
            ->will($this->returnValue($topic))
        ;
        $session
            ->expects($this->once())
            ->method('createQueueQueue')
            ->with('queue-name')
            ->will($this->returnValue($queue))
        ;

        $producer = new QueueProducer($session, new Config('', '', '', '', 'default-queue-name'));
        $producer->send($message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected function createSessionMock()
    {
        return $this->getMock(Session::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TransportSession
     */
    protected function createTransportSessionMock()
    {
        return $this->getMock(TransportSession::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducer
     */
    protected function createMessageProducer()
    {
        return $this->getMock(MessageProducer::class, [], [], '', false);
    }
}
