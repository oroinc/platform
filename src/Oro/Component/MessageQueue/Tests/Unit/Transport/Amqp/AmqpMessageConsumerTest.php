<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessageConsumer;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpQueue;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpSession;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageConsumer;
use Oro\Component\Testing\ClassExtensionTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;
use PhpAmqpLib\Wire\AMQPTable;

// @codingStandardsIgnoreStart

class AmqpMessageConsumerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageConsumerInterface()
    {
        $this->assertClassImplements(MessageConsumer::class, AmqpMessageConsumer::class);
    }

    public function testCouldBeConstructedWithSessionAndQueueAsArguments()
    {
        new AmqpMessageConsumer($this->createAmqpSessionStub(), new AmqpQueue('aName'));
    }

    public function testShouldSubscribeToQueueOnFirstReceiveCall()
    {
        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('basic_consume')
            ->with('theQueueName')
        ;
        $channelMock
            ->expects($this->exactly(2))
            ->method('wait')
        ;

        $sessionStub = $this->createAmqpSessionStub($channelMock);

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('theQueueName'));

        $consumer->receive();
        $consumer->receive();
    }

    public function testShouldCorrectlyPassQueueOptionsToBasicConsumeMethod()
    {
        $queue = new AmqpQueue('aQueueName');
        $queue->setConsumerTag('theConsumerTag');
        $queue->setNoLocal('theLocalBool');
        $queue->setNoAck('theAskBool');
        $queue->setExclusive('theExclusiveBool');
        $queue->setNoWait('theNoWaitBool');

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('basic_consume')
            ->with($this->anything(), 'theConsumerTag', 'theLocalBool', 'theAskBool', 'theExclusiveBool', 'theNoWaitBool')
        ;

        $sessionStub = $this->createAmqpSessionStub($channelMock);

        $consumer = new AmqpMessageConsumer($sessionStub, $queue);

        $consumer->receive();
    }

    public function testShouldRegisterCreateMessageCallbackOnFirstReceiveCall()
    {
        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('basic_consume')
            ->willReturnCallback(function () {
                $this->assertInstanceOf('Closure', func_get_arg(6));
            })
        ;
        $channelMock
            ->expects($this->exactly(2))
            ->method('wait')
        ;

        $sessionStub = $this->createAmqpSessionStub($channelMock);

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('aName'));

        $consumer->receive();
        $consumer->receive();
    }

    public function testShouldPassTimeoutWhileCallingChannelWaitMethod()
    {
        $expectedTimeout = 123123123;

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('wait')
            ->with($this->anything(), $this->anything(), $expectedTimeout)
        ;

        $sessionStub = $this->createAmqpSessionStub($channelMock);

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('aName'));

        $consumer->receive($expectedTimeout);
    }

    public function testShouldWaitForMessageAndReturnItOnReceive()
    {
        $expectedMessage = new AmqpMessage();
        $expectedInternalMessage = new AMQPLibMessage();
        $expectedInternalMessage->delivery_info['delivery_tag'] = 'theDeliveryTag';
        $expectedInternalMessage->delivery_info['redelivered'] = 'theRedeliveredBool';
        $expectedInternalMessage->delivery_info['exchange'] = 'theExchange';

        $channelStub = new AMQPChannelStub();
        $channelStub->receivedInternalMessage = $expectedInternalMessage;

        $sessionStub = $this->createAmqpSessionStub($channelStub);
        $sessionStub
            ->expects($this->once())
            ->method('createMessage')
            ->with(null, [], [])
            ->willReturn($expectedMessage)
        ;

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('aName'));

        $actualMessage = $consumer->receive();
        $this->assertSame($expectedMessage, $actualMessage);
        $this->assertSame('theDeliveryTag', $actualMessage->getDeliveryTag());
        $this->assertSame('theRedeliveredBool', $actualMessage->isRedelivered());
        $this->assertSame('theExchange', $actualMessage->getExchange());
    }

    public function testShouldCorrectlyExtractInternalMessageBodyAndPassItMessageFactory()
    {
        $internalMessage = new AMQPLibMessage('theMessageBody');
        $internalMessage->delivery_info['delivery_tag'] = 'aTag';
        $internalMessage->delivery_info['redelivered'] = 'aRedeliveredBool';
        $internalMessage->delivery_info['exchange'] = 'aExchange';

        $channelStub = new AMQPChannelStub();
        $channelStub->receivedInternalMessage = $internalMessage;

        $sessionStub = $this->createAmqpSessionStub($channelStub);
        $sessionStub
            ->expects($this->once())
            ->method('createMessage')
            ->with('theMessageBody')
            ->willReturn(new AmqpMessage())
        ;

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('aName'));

        // guard
        $this->assertNotNull($consumer->receive());
    }

    public function testShouldCorrectlyExtractInternalMessagePropertiesAndPassItMessageFactory()
    {
        $internalMessage = new AMQPLibMessage('theMessageBody');
        $internalMessage->set('application_headers', new AMQPTable(['theProp' => 'thePropVal']));
        $internalMessage->delivery_info['delivery_tag'] = 'aTag';
        $internalMessage->delivery_info['redelivered'] = 'aRedeliveredBool';
        $internalMessage->delivery_info['exchange'] = 'aExchange';

        $channelStub = new AMQPChannelStub();
        $channelStub->receivedInternalMessage = $internalMessage;

        $sessionStub = $this->createAmqpSessionStub($channelStub);
        $sessionStub
            ->expects($this->once())
            ->method('createMessage')
            ->with($this->anything(), ['theProp' => 'thePropVal'])
            ->willReturn(new AmqpMessage())
        ;

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('aName'));

        // guard
        $this->assertNotNull($consumer->receive());
    }

    public function testShouldCorrectlyExtractInternalMessageHeadersAndPassItMessageFactory()
    {
        $internalMessage = new AMQPLibMessage('theMessageBody', ['timestamp' => 123123123]);
        $internalMessage->delivery_info['delivery_tag'] = 'aTag';
        $internalMessage->delivery_info['redelivered'] = 'aRedeliveredBool';
        $internalMessage->delivery_info['exchange'] = 'aExchange';

        $channelStub = new AMQPChannelStub();
        $channelStub->receivedInternalMessage = $internalMessage;

        $sessionStub = $this->createAmqpSessionStub($channelStub);
        $sessionStub
            ->expects($this->once())
            ->method('createMessage')
            ->with($this->anything(), $this->anything(), ['timestamp' => 123123123])
            ->willReturn(new AmqpMessage())
        ;

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('aName'));

        // guard
        $this->assertNotNull($consumer->receive());
    }

    public function testShouldReturnNullIfWaitTimeOutedWithoutReceivingInternalMessage()
    {
        $timeout = 5;

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('wait')
            ->with($this->anything(), $this->anything(), $timeout)
            ->willReturn(null)
        ;

        $sessionStub = $this->createAmqpSessionStub($channelMock);

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('aName'));

        $this->assertNull($consumer->receive($timeout));
    }

    public function testShouldReturnNullIfWaitTimeOutedWithException()
    {
        $timeout = 5;

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('wait')
            ->with($this->anything(), $this->anything(), $timeout)
            ->willThrowException(new AMQPTimeoutException())
        ;

        $sessionStub = $this->createAmqpSessionStub($channelMock);

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('aName'));

        $this->assertNull($consumer->receive($timeout));
    }

    public function testThrowIfGivenDestinationInvalidOnAcknowledge()
    {
        $consumer = new AmqpMessageConsumer($this->createAmqpSessionStub(), new AmqpQueue('aName'));

        $invalidMessage = $this->createMessage();

        $this->setExpectedException(
            InvalidMessageException::class,
            'A message is invalid. Message must be an instance of Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage'
        );

        $consumer->acknowledge($invalidMessage);
    }

    public function testShouldAcknowledgeMessage()
    {
        $expectedDeliveryTag = 'theDeliveryTag';

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('basic_ack')
            ->with($expectedDeliveryTag)
        ;

        $sessionStub = $this->createAmqpSessionStub($channelMock);

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('aName'));

        $message = new AmqpMessage();
        $message->setDeliveryTag($expectedDeliveryTag);

        $consumer->acknowledge($message);
    }

    public function testThrowIfGivenDestinationInvalidOnReject()
    {
        $consumer = new AmqpMessageConsumer($this->createAmqpSessionStub(), new AmqpQueue('aName'));

        $invalidMessage = $this->createMessage();

        $this->setExpectedException(
            InvalidMessageException::class,
            'A message is invalid. Message must be an instance of Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage'
        );

        $consumer->reject($invalidMessage);
    }

    public function testShouldRejectMessageWithoutRequeueByDefault()
    {
        $expectedDeliveryTag = 'theDeliveryTag';

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('basic_reject')
            ->with($expectedDeliveryTag, $requeue = false)
        ;

        $sessionStub = $this->createAmqpSessionStub($channelMock);

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('aName'));

        $message = new AmqpMessage();
        $message->setDeliveryTag($expectedDeliveryTag);

        $consumer->reject($message);
    }

    public function testShouldRejectMessageWithRequeuePassedExplicitly()
    {
        $expectedDeliveryTag = 'theDeliveryTag';

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('basic_reject')
            ->with($expectedDeliveryTag, $requeue = true)
        ;

        $sessionStub = $this->createAmqpSessionStub($channelMock);

        $consumer = new AmqpMessageConsumer($sessionStub, new AmqpQueue('aName'));

        $message = new AmqpMessage();
        $message->setDeliveryTag($expectedDeliveryTag);

        $consumer->reject($message, true);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Message
     */
    protected function createMessage()
    {
        return $this->getMock(Message::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    protected function createAmqpChannel()
    {
        return $this->getMock(AMQPChannel::class, [], [], '', false);
    }

    /**
     * @param AMQPChannel $ampqChannel
     *
     * @return AmqpSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAmqpSessionStub(AMQPChannel $ampqChannel = null)
    {
        $sessionMock = $this->getMock(AmqpSession::class, [], [], '', false);
        $sessionMock
            ->expects($this->any())
            ->method('getChannel')
            ->willReturn($ampqChannel)
        ;

        return $sessionMock;
    }
}

class AMQPChannelStub extends AMQPChannel
{
    /**
     * @var AMQPLibMessage
     */
    public $receivedInternalMessage;

    protected $callback;

    public function __construct()
    {
    }

    public function wait($allowed_methods = null, $non_blocking = false, $timeout = 0)
    {
        call_user_func($this->callback, $this->receivedInternalMessage);
    }

    public function basic_consume(
        $queue = '',
        $gconsumer_tag = '',
        $no_local = false,
        $no_ack = false,
        $exclusive = false,
        $nowait = false,
        $callback = null,
        $ticket = null,
        $arguments = array()
    ) {
        $this->callback = $callback;
    }
}

// @codingStandardsIgnoreEnd
