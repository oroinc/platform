<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface as TransportMessageProducer;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Client\NullDriver;
use Oro\Component\MessageQueue\Client\Config;

class NullDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new NullDriver($this->createSessionMock(), new Config('', '', '', '', ''));
    }

    public function testShouldCreateMessageInstance()
    {
        $message = new NullMessage();

        $transportSession = $this->createSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createMessage')
            ->with(null, [], [])
            ->will($this->returnValue($message))
        ;

        $driver = new NullDriver($transportSession, new Config('', '', '', '', ''));
        $result = $driver->createMessage();

        $this->assertSame($message, $result);
    }

    public function testShouldSetMessagePriority()
    {
        $message = new NullMessage();

        $session = $this->createSessionMock();

        $driver = new NullDriver($session, new Config('', '', '', '', ''));
        $driver->setMessagePriority($message, $priority = 3);

        $this->assertSame($priority, $message->getHeader('priority'));
    }

    public function testShouldCreateProducerInstance()
    {
        $transportSession = $this->createSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue($this->getMock(TransportMessageProducer::class)))
        ;

        $driver = new NullDriver($transportSession, new Config('', '', '', '', ''));
        $result = $driver->createProducer();

        $this->assertInstanceOf(MessageProducer::class, $result);
    }

    public function testShouldReturnConfigInstance()
    {
        $config = new Config('', '', '', '', '');

        $driver = new NullDriver($this->createSessionMock(), $config);
        $result = $driver->getConfig();

        $this->assertSame($config, $result);
    }

    public function testShouldCreateQueue()
    {
        $queue = new NullQueue('');

        $config = new Config('', '', '', '', '');

        $transportSession = $this->createSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createQueue')
            ->with('queue-name')
            ->will($this->returnValue($queue))
        ;

        $driver = new NullDriver($transportSession, $config);
        $result = $driver->createQueue('queue-name');

        $this->assertSame($queue, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|NullSession
     */
    protected function createSessionMock()
    {
        return $this->getMock(NullSession::class, [], [], '', false);
    }
}
