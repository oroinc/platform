<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig;

use Oro\Component\Messaging\Transport\MessageProducer;
use Oro\Component\Messaging\Transport\Null\NullMessage;
use Oro\Component\Messaging\Transport\Null\NullQueue;
use Oro\Component\Messaging\Transport\Null\NullTopic;
use Oro\Component\Messaging\Transport\Session as TransportSession;
use Oro\Component\Messaging\ZeroConfig\FrontProducer;
use Oro\Component\Messaging\ZeroConfig\Config;
use Oro\Component\Messaging\ZeroConfig\Session;

class FrontProducerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new FrontProducer($this->createSessionMock(), new Config('', '', '', '', ''));
    }

    public function testShouldSendMessageAndCreateSchema()
    {
        $topic = new NullTopic('topic');
        $queue = new NullQueue('queue');

        $message = new NullMessage();


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
            ->method('createMessage')
            ->will($this->returnValue($message))
        ;
        $session
            ->expects($this->once())
            ->method('getTransportSession')
            ->will($this->returnValue($transportSession))
        ;
        $session
            ->expects($this->once())
            ->method('createRouterTopic')
            ->will($this->returnValue($topic))
        ;
        $session
            ->expects($this->once())
            ->method('createRouterQueue')
            ->will($this->returnValue($queue))
        ;

        $producer = new FrontProducer($session, new Config('', '', '', '', ''));
        $producer->send('name', 'body');
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
