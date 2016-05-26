<?php
namespace Oro\Component\MessageQueue\Tests\Transport\Null;

use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullMessageConsumer;
use Oro\Component\MessageQueue\Transport\Null\NullMessageProducer;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Transport\Null\NullTopic;
use Oro\Component\MessageQueue\Transport\Session;
use Oro\Component\Testing\ClassExtensionTrait;

class NullSessionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementSessionInterface()
    {
        $this->assertClassImplements(Session::class, NullSession::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new NullSession();
    }

    public function testShouldAllowCreateMessageWithoutAnyArguments()
    {
        $session = new NullSession();

        $message = $session->createMessage();

        $this->assertInstanceOf(NullMessage::class, $message);

        $this->assertSame(null, $message->getBody());
        $this->assertSame([], $message->getHeaders());
        $this->assertSame([], $message->getProperties());
    }

    public function testShouldAllowCreateCustomMessage()
    {
        $session = new NullSession();

        $message = $session->createMessage('theBody', ['theProperty'], ['theHeader']);

        $this->assertInstanceOf(NullMessage::class, $message);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['theProperty'], $message->getProperties());
        $this->assertSame(['theHeader'], $message->getHeaders());
    }

    public function testShouldAllowCreateQueue()
    {
        $session = new NullSession();

        $queue = $session->createQueue('aName');
        
        $this->assertInstanceOf(NullQueue::class, $queue);
    }

    public function testShouldAllowCreateTopic()
    {
        $session = new NullSession();

        $topic = $session->createTopic('aName');

        $this->assertInstanceOf(NullTopic::class, $topic);
    }

    public function testShouldAllowCreateConsumerForGivenQueue()
    {
        $session = new NullSession();

        $queue = new NullQueue('aName');

        $consumer = $session->createConsumer($queue);

        $this->assertInstanceOf(NullMessageConsumer::class, $consumer);
    }

    public function testShouldAllowCreateProducer()
    {
        $session = new NullSession();

        $producer = $session->createProducer();

        $this->assertInstanceOf(NullMessageProducer::class, $producer);
    }

    public function testShouldDoNothingOnDeclareQueue()
    {
        $queue = new NullQueue('theQueueName');

        $session = new NullSession();
        $session->declareQueue($queue);
    }

    public function testShouldDoNothingOnDeclareTopic()
    {
        $topic = new NullTopic('theTopicName');

        $session = new NullSession();
        $session->declareTopic($topic);
    }

    public function testShouldDoNothingOnDeclareBind()
    {
        $topic = new NullTopic('theTopicName');
        $queue = new NullQueue('theQueueName');

        $session = new NullSession();
        $session->declareBind($topic, $queue);
    }
}
