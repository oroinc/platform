<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DbalDriver;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalDestination;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageProducer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;

class DbalDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DbalDriver($this->createDBALSessionMock(), new Config('', '', '', ''));
    }

    public function testShouldCreateProducerInstance()
    {
        $session = $this->createDBALSessionMock();
        $session
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue($this->createDBALMessageProducerMock()))
        ;

        $driver = new DbalDriver($session, new Config('', '', '', ''));

        $this->assertInstanceOf(MessageProducer::class, $driver->createProducer());
    }

    public function testShouldCreateMessageInstance()
    {
        $message = new DbalMessage();

        $session = $this->createDBALSessionMock();
        $session
            ->expects($this->once())
            ->method('createMessage')
            ->will($this->returnValue($message))
        ;

        $driver = new DbalDriver($session, new Config('', '', '', ''));

        $this->assertSame($message, $driver->createMessage());
    }

    public function testThrowIfGivenPriorityNotSupported()
    {
        $message = new DbalMessage();

        $session = $this->createDBALSessionMock();

        $driver = new DbalDriver($session, new Config('', '', '', '', ''));

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Given priority could not be converted to transport\'s one. Got: notSupportedPriority'
        );
        $driver->setMessagePriority($message, $priority = 'notSupportedPriority');
    }

    public function providePriorities()
    {
        return [
            [MessagePriority::VERY_LOW, 0],
            [MessagePriority::LOW, 1],
            [MessagePriority::NORMAL, 2],
            [MessagePriority::HIGH, 3],
            [MessagePriority::VERY_HIGH, 4],
        ];
    }

    /**
     * @dataProvider providePriorities
     */
    public function testCorrectlyConvertClientsPriorityToTransportsPriority($clientPriority, $transportPriority)
    {
        $message = new DbalMessage();

        $session = $this->createDBALSessionMock();

        $driver = new DbalDriver($session, new Config('', '', '', '', ''));

        $driver->setMessagePriority($message, $clientPriority);

        $this->assertSame($transportPriority, $message->getPriority());
    }

    public function testShouldCreateAndDeclareQueue()
    {
        $queue = new DbalDestination('name');

        $session = $this->createDBALSessionMock();
        $session
            ->expects($this->once())
            ->method('createQueue')
            ->with('queue')
            ->will($this->returnValue($queue))
        ;
        $session
            ->expects($this->once())
            ->method('declareQueue')
            ->with($this->identicalTo($queue))
        ;

        $driver = new DbalDriver($session, new Config('', '', '', ''));

        $this->assertSame($queue, $driver->createQueue('queue'));
    }

    public function testShouldSendDelayedMessage()
    {
        $queue = new DbalDestination('');
        $message = new DbalMessage();
        $message->setBody('body');
        $message->setHeaders(['hkey' => 'hval']);
        $message->setProperties(['pkey' => 'pval']);

        $delayedMessage = new DbalMessage();

        $producer = $this->createDBALMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($delayedMessage))
        ;

        $session = $this->createDBALSessionMock();
        $session
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue($producer))
        ;
        $session
            ->expects($this->once())
            ->method('createMessage')
            ->with('body', ['pkey' => 'pval'], ['hkey' => 'hval'])
            ->will($this->returnValue($delayedMessage))
        ;

        $driver = new DbalDriver($session, new Config('', '', '', ''));
        $driver->delayMessage($queue, $message, 12345);

        $this->assertSame(12345, $delayedMessage->getDelay());
    }

    public function testShouldReturnConfigInstance()
    {
        $config = new Config('', '', '', '');

        $driver = new DbalDriver($this->createDBALSessionMock(), $config);

        $this->assertSame($config, $driver->getConfig());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalMessageProducer
     */
    private function createDBALMessageProducerMock()
    {
        return $this->getMock(DbalMessageProducer::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalSession
     */
    private function createDBALSessionMock()
    {
        return $this->getMock(DbalSession::class, [], [], '', false);
    }
}
