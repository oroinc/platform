<?php
namespace Oro\Component\Messaging\Tests\Transport\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use Oro\Component\Messaging\Transport\Amqp\AmqpMessageConsumer;
use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession;
use Oro\Component\Messaging\Transport\Message;
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
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\MessageConsumer',
            'Oro\Component\Messaging\Transport\Amqp\AmqpMessageConsumer'
        );
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

    public function testShouldWaitForInternalMessageAndReturnItOnReceive()
    {
        $expectedMessage = new AmqpMessage();
        $expectedInternalMessage = new AMQPLibMessage();

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
        $this->assertSame($expectedInternalMessage, $actualMessage->getInternalMessage());
    }

    public function testShouldCorrectlyExtractInternalMessageBodyAndPassItMessageFactory()
    {
        $internalMessage = new AMQPLibMessage('theMessageBody');

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

    /**
     * @expectedException \Oro\Component\Messaging\Transport\Exception\InvalidMessageException
     * @expectedExceptionMessage A message is invalid. Message must be an instance of Oro\Component\Messaging\Transport\Amqp\AmqpMessage
     */
    public function testThrowIfGivenDestinationInvalidOnAcknowledge()
    {
        $consumer = new AmqpMessageConsumer($this->createAmqpSessionStub(), new AmqpQueue('aName'));

        $invalidMessage = $this->createMessage();

        $consumer->acknowledge($invalidMessage);
    }

    /**
     * @expectedException \Oro\Component\Messaging\Transport\Exception\InvalidMessageException
     * @expectedExceptionMessage A message does not have an internal message associated. Could not be acknowledged
     */
    public function testThrowIfGivenMessageNotHaveInternalMessageSetOnAcknowledge()
    {
        $consumer = new AmqpMessageConsumer($this->createAmqpSessionStub(), new AmqpQueue('aName'));

        $message = new AmqpMessage();

        $consumer->acknowledge($message);
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

        $internalMessage = new AMQPLibMessage();
        $internalMessage->delivery_info['delivery_tag'] = $expectedDeliveryTag;
        $message = new AmqpMessage();
        $message->setInternalMessage($internalMessage);

        $consumer->acknowledge($message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Message
     */
    protected function createMessage()
    {
        return $this->getMock('Oro\Component\Messaging\Transport\Message');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    protected function createAmqpChannel()
    {
        return $this->getMock('PhpAmqpLib\Channel\AMQPChannel', [], [], '', false);
    }

    /**
     * @param AMQPChannel $ampqChannel
     *
     * @return AmqpSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAmqpSessionStub(AMQPChannel $ampqChannel = null)
    {
        $sessionMock = $this->getMock('Oro\Component\Messaging\Transport\Amqp\AmqpSession', [], [], '', false);
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

    public function wait()
    {
        call_user_func($this->callback, $this->receivedInternalMessage);
    }

    public function basic_consume()
    {
        // see parent's basic_consume method arguments for more details.
        $this->callback = func_get_arg(6);
    }
}

// @codingStandardsIgnoreEnd
