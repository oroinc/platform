<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig;

use Oro\Component\Messaging\Transport\MessageProducer;
use Oro\Component\Messaging\Transport\Null\NullMessage;
use Oro\Component\Messaging\Transport\Null\NullQueue;
use Oro\Component\Messaging\ZeroConfig\FrontProducer;
use Oro\Component\Messaging\ZeroConfig\Config;
use Oro\Component\Messaging\ZeroConfig\Session;

class FrontProducerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new FrontProducer($this->createSessionMock());
    }

    public function testShouldSendMessageAndCreateSchema()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($message))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('createMessage')
            ->will($this->returnValue($message))
        ;
        $session
            ->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config))
        ;
        $session
            ->expects($this->once())
            ->method('createQueue')
            ->will($this->returnValue($queue))
        ;
        $session
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue($messageProducer))
        ;

        $producer = new FrontProducer($session);
        $producer->send('topic', 'message');

        $expectedProperties = [
            'oro.messaging.zero_conf.topic_name' => 'topic',
            'oro.messaging.zero_conf.processor_name' => 'route-message-processor',
            'oro.messaging.zero_conf.queue_name' => 'router-queue',
        ];

        $this->assertEquals($expectedProperties, $message->getProperties());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected function createSessionMock()
    {
        return $this->getMock(Session::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducer
     */
    protected function createMessageProducer()
    {
        return $this->getMock(MessageProducer::class, [], [], '', false);
    }
}
