<?php
namespace Oro\Component\MessageQueue\Tests\ZeroConfig;

use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\Transport\Null\NullSession as TransportNullSession;
use Oro\Component\MessageQueue\ZeroConfig\FrontProducer;
use Oro\Component\MessageQueue\ZeroConfig\NullSession;
use Oro\Component\MessageQueue\ZeroConfig\Config;

class NullSessionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new NullSession($this->createTransportSessionMock(), new Config('', '', '', '', ''));
    }

    public function testShouldCreateMessageInstance()
    {
        $message = new NullMessage();

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createMessage')
            ->with(null, [], [])
            ->will($this->returnValue($message))
        ;

        $session = new NullSession($transportSession, new Config('', '', '', '', ''));
        $result = $session->createMessage();

        $this->assertSame($message, $result);
    }

    public function testShouldCreateProducerInstance()
    {
        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue('producer-instance'))
        ;

        $session = new NullSession($transportSession, new Config('', '', '', '', ''));
        $result = $session->createProducer();

        $this->assertEquals('producer-instance', $result);
    }

    public function testShouldCreateFrontProducerInstance()
    {
        $session = new NullSession($this->createTransportSessionMock(), new Config('', '', '', '', ''));
        $result = $session->createFrontProducer();

        $this->assertInstanceOf(FrontProducer::class, $result);
    }

    public function testShouldReturnConfigInstance()
    {
        $config = new Config('', '', '', '', '');

        $session = new NullSession($this->createTransportSessionMock(), $config);
        $result = $session->getConfig();

        $this->assertSame($config, $result);
    }

    public function testShouldCreateQueue()
    {
        $queue = new NullQueue('');

        $config = new Config('', '', '', '', '');

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createQueue')
            ->with('queue-name')
            ->will($this->returnValue($queue))
        ;

        $session = new NullSession($transportSession, $config);
        $result = $session->createQueue('queue-name');

        $this->assertSame($queue, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TransportNullSession
     */
    protected function createTransportSessionMock()
    {
        return $this->getMock(TransportNullSession::class, [], [], '', false);
    }
}
