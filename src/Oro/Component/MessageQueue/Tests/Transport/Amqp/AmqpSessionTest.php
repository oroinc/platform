<?php
namespace Oro\Component\MessageQueue\Tests\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessageConsumer;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessageProducer;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpQueue;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpSession;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpTopic;
use Oro\Component\MessageQueue\Transport\Destination;
use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\Session;
use Oro\Component\Testing\ClassExtensionTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpSessionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementSessionInterface()
    {
        $this->assertClassImplements(Session::class, AmqpSession::class);
    }

    public function testCouldBeConstructedWithChannelAsArgument()
    {
        new AmqpSession($this->createAmqpChannel());
    }

    public function testShouldAllowCreateMessageWithoutAnyArguments()
    {
        $session = new AmqpSession($this->createAmqpChannel());

        $message = $session->createMessage();

        $this->assertInstanceOf(AmqpMessage::class, $message);

        $this->assertSame(null, $message->getBody());
        $this->assertSame([], $message->getHeaders());
        $this->assertSame([], $message->getProperties());
    }

    public function testShouldAllowCreateCustomMessage()
    {
        $session = new AmqpSession($this->createAmqpChannel());

        $message = $session->createMessage('theBody', ['theProperty'], ['theHeader']);

        $this->assertInstanceOf(AmqpMessage::class, $message);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['theProperty'], $message->getProperties());
        $this->assertSame(['theHeader'], $message->getHeaders());
    }

    public function testShouldAllowCreateQueue()
    {
        $session = new AmqpSession($this->createAmqpChannel());

        $queue = $session->createQueue('aName');
        
        $this->assertInstanceOf(AmqpQueue::class, $queue);
    }

    public function testShouldAllowCreateTopic()
    {
        $session = new AmqpSession($this->createAmqpChannel());

        $topic = $session->createTopic('aName');

        $this->assertInstanceOf(AmqpTopic::class, $topic);
    }

    public function testShouldAllowCreateConsumerForGivenQueue()
    {
        $session = new AmqpSession($this->createAmqpChannel());

        $queue = new AmqpQueue('aName');

        $consumer = $session->createConsumer($queue);

        $this->assertInstanceOf(AmqpMessageConsumer::class, $consumer);
    }

    public function testThrowIfGivenDestinationInvalidOnCreateConsumer()
    {
        $session = new AmqpSession($this->createAmqpChannel());

        $invalidDestination = $this->createDestination();

        $this->setExpectedException(InvalidDestinationException::class);

        $session->createConsumer($invalidDestination);
    }

    public function testShouldAllowCreateProducer()
    {
        $session = new AmqpSession($this->createAmqpChannel());

        $producer = $session->createProducer();

        $this->assertInstanceOf(AmqpMessageProducer::class, $producer);
    }

    public function testShouldAllowDeclareQueue()
    {
        $queue = new AmqpQueue('theQueueName');

        $channelMock = $this->createAmqpChannel();

        $channelMock->expects($this->once())
            ->method('queue_declare')
            ->with('theQueueName')
        ;

        $session = new AmqpSession($channelMock);
        $session->declareQueue($queue);
    }

    public function testShouldCorrectlyPassQueueOptionsToQueueDeclareMethod()
    {
        $queue = new AmqpQueue('aTopicName');
        $queue->setDurable('theDurableBool');
        $queue->setPassive('thePassiveBool');
        $queue->setExclusive('theExclusiveBool');
        $queue->setAutoDelete('theAutoDeleteBool');
        $queue->setNoWait('theNoWaitBool');
        $queue->setTable(['theKey' => 'theVal']);

        $channelMock = $this->createAmqpChannel();

        $channelMock->expects($this->once())
            ->method('queue_declare')
            ->with(
                $this->anything(),
                'thePassiveBool',
                'theDurableBool',
                'theExclusiveBool',
                'theAutoDeleteBool',
                'theNoWaitBool',
                new AMQPTable(['theKey' => 'theVal'])
            )
        ;

        $session = new AmqpSession($channelMock);
        $session->declareQueue($queue);
    }

    public function testThrowIfGivenDestinationInvalidOnDeclareQueue()
    {
        $session = new AmqpSession($this->createAmqpChannel());

        $invalidDestination = $this->createDestination();

        $this->setExpectedException(InvalidDestinationException::class);

        $session->declareQueue($invalidDestination);
    }

    public function testShouldAllowDeclareTopic()
    {
        $topic = new AmqpTopic('theTopicName');

        $channelMock = $this->createAmqpChannel();

        $channelMock->expects($this->once())
            ->method('exchange_declare')
            ->with('theTopicName')
        ;

        $session = new AmqpSession($channelMock);
        $session->declareTopic($topic);
    }

    public function testShouldCorrectlyPassTopicOptionsToExchangeDeclareMethod()
    {
        $topic = new AmqpTopic('aTopicName');
        $topic->setType('theTopicType');
        $topic->setDurable('theDurableBool');
        $topic->setPassive('thePassiveBool');
        $topic->setNoWait('theNoWaitBool');
        $topic->setTable(['theKey' => 'theVal']);

        $channelMock = $this->createAmqpChannel();

        $channelMock->expects($this->once())
            ->method('exchange_declare')
            ->with(
                $this->anything(),
                'theTopicType',
                'thePassiveBool',
                'theDurableBool',
                false,
                false,
                'theNoWaitBool',
                new AMQPTable(['theKey' => 'theVal'])
            )
        ;

        $session = new AmqpSession($channelMock);
        $session->declareTopic($topic);
    }

    public function testThrowIfGivenDestinationInvalidOnDeclareTopic()
    {
        $session = new AmqpSession($this->createAmqpChannel());

        $invalidDestination = $this->createDestination();

        $this->setExpectedException(InvalidDestinationException::class);

        $session->declareTopic($invalidDestination);
    }

    public function testShouldAllowDeclareBindBetweenSourceAndTargetDestinations()
    {
        $topic = new AmqpTopic('theTopicName');
        $queue = new AmqpQueue('theQueueName');

        $channelMock = $this->createAmqpChannel();

        $channelMock->expects($this->once())
            ->method('queue_bind')
            ->with('theQueueName', 'theTopicName')
        ;

        $session = new AmqpSession($channelMock);
        $session->declareBind($topic, $queue);
    }

    public function testThrowIfGivenSourceDestinationInvalidOnDeclareBind()
    {
        $session = new AmqpSession($this->createAmqpChannel());

        $invalidDestination = $this->createDestination();

        $this->setExpectedException(InvalidDestinationException::class);

        $session->declareBind($invalidDestination, new AmqpQueue('aName'));
    }

    public function testThrowIfGivenTargetDestinationInvalidOnDeclareBind()
    {
        $session = new AmqpSession($this->createAmqpChannel());

        $invalidDestination = $this->createDestination();

        $this->setExpectedException(InvalidDestinationException::class);

        $session->declareBind(new AmqpTopic('aName'), $invalidDestination);
    }
    
    public function testShouldCallChannelCloseMethodOnClose()
    {
        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('close')
        ;
        
        $session = new AmqpSession($channelMock);
        
        $session->close();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    protected function createAmqpChannel()
    {
        return $this->getMock(AMQPChannel::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Destination
     */
    protected function createDestination()
    {
        return $this->getMock(Destination::class);
    }
}
