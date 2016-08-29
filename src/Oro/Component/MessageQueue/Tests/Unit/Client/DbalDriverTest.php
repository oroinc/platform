<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DbalDriver;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Transport\Dbal\DbalDestination;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageProducer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;

class DbalDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DbalDriver($this->createSessionMock(), new Config('', '', '', '', ''));
    }

    public function testShouldSendJustCreatedMessageToQueue()
    {
        $config = new Config('', '', '', '', '');
        $queue = new DbalDestination('aQueue');

        $transportMessage = new DbalMessage();

        $producer = $this->createMessageProducer();
        $producer
            ->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($queue), self::identicalTo($transportMessage))
        ;

        $session = $this->createSessionStub($transportMessage, $producer);

        $driver = new DbalDriver($session, $config);

        $driver->send($queue, new Message());
    }

    public function testShouldConvertClientMessageToTransportMessage()
    {
        $config = new Config('', '', '', '', '');
        $queue = new DbalDestination('aQueue');

        $message = new Message();
        $message->setBody('theBody');
        $message->setContentType('theContentType');
        $message->setMessageId('theMessageId');
        $message->setTimestamp(12345);
        $message->setHeaders(['theHeaderFoo' => 'theFoo']);
        $message->setProperties(['thePropertyBar' => 'theBar']);

        $transportMessage = new DbalMessage();

        $producer = $this->createMessageProducer();
        $producer
            ->expects(self::once())
            ->method('send')
        ;

        $session = $this->createSessionStub($transportMessage, $producer);

        $driver = new DbalDriver($session, $config);

        $driver->send($queue, $message);

        self::assertSame('theBody', $transportMessage->getBody());
        self::assertSame([
            'theHeaderFoo' => 'theFoo',
            'content_type' => 'theContentType',
            'message_id' => 'theMessageId',
            'timestamp' => 12345
        ], $transportMessage->getHeaders());
        self::assertSame([
            'thePropertyBar' => 'theBar',
        ], $transportMessage->getProperties());
    }

    public function testShouldThrowNotImplementedIfExpirationHeaderIsSet()
    {
        $config = new Config('', '', '', '', '');
        $queue = new DbalDestination('aQueue');

        $message = new Message();
        $message->setExpire(123);

        $transportMessage = new DbalMessage();

        $producer = $this->createMessageProducer();
        $producer
            ->expects(self::never())
            ->method('send')
        ;

        $session = $this->createSessionStub($transportMessage, $producer);

        $driver = new DbalDriver($session, $config);

        $this->setExpectedException(\LogicException::class, 'Expire is not supported by the transport');
        $driver->send($queue, $message);
    }

    /**
     * @dataProvider providePriorities
     */
    public function testCorrectlyConvertClientsPriorityToTransportsPriority($clientPriority, $transportPriority)
    {
        $config = new Config('', '', '', '', '');
        $queue = new DbalDestination('aQueue');

        $message = new Message();
        $message->setPriority($clientPriority);

        $transportMessage = new DbalMessage();

        $producer = $this->createMessageProducer();
        $producer
            ->expects(self::once())
            ->method('send')
        ;

        $session = $this->createSessionStub($transportMessage, $producer);

        $driver = new DbalDriver($session, $config);

        $driver->send($queue, $message);

        self::assertSame($transportPriority, $transportMessage->getPriority());
    }

    public function testShouldReturnConfigInstance()
    {
        $config = new Config('', '', '', '', '');

        $driver = new DbalDriver($this->createSessionMock(), $config);
        $result = $driver->getConfig();

        self::assertSame($config, $result);
    }

    public function testAllowCreateTransportMessage()
    {
        $config = new Config('', '', '', '', '');

        $message = new DbalMessage();

        $session = $this->createSessionMock();
        $session
            ->expects(self::once())
            ->method('createMessage')
            ->willReturn($message)
        ;

        $driver = new DbalDriver($session, $config);

        self::assertSame($message, $driver->createTransportMessage());
    }

    public function testShouldCreateAndDeclareQueue()
    {
        $queue = new DbalDestination('name');
        $session = $this->createSessionMock();
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

    public function testShouldSetDelayHeaderIfSetInClientMessage()
    {
        $config = new Config('', '', '', '', '');
        $queue = new DbalDestination('aQueue');

        $message = new Message();
        $message->setDelay(123);

        $transportMessage = new DbalMessage();

        $producer = $this->createMessageProducer();
        $producer
            ->expects(self::once())
            ->method('send')
        ;

        $session = $this->createSessionStub($transportMessage, $producer);

        $driver = new DbalDriver($session, $config);

        $driver->send($queue, $message);

        self::assertSame(123, $transportMessage->getDelay());
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
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalSession
     */
    private function createSessionStub($message = null, $messageProducer = null)
    {
        $sessionMock = $this->getMock(DbalSession::class, [], [], '', false);
        $sessionMock
            ->expects($this->any())
            ->method('createMessage')
            ->willReturn($message)
        ;
        $sessionMock
            ->expects($this->any())
            ->method('createQueue')
            ->willReturnCallback(function ($name) {
                return new DbalDestination($name);
            })
        ;
        $sessionMock
            ->expects($this->any())
            ->method('createProducer')
            ->willReturn($messageProducer)
        ;

        return $sessionMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalMessageProducer
     */
    private function createMessageProducer()
    {
        return $this->getMock(DbalMessageProducer::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalSession
     */
    private function createSessionMock()
    {
        return $this->getMock(DbalSession::class, [], [], '', false);
    }
}
