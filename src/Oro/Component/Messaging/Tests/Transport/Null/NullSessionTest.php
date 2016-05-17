<?php
namespace Oro\Component\Messaging\Tests\Transport\Null;

use Oro\Component\Messaging\Transport\Null\NullQueue;
use Oro\Component\Messaging\Transport\Null\NullSession;
use Oro\Component\Messaging\Transport\Null\NullTopic;
use Oro\Component\Testing\ClassExtensionTrait;

class NullSessionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementSessionInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\Session',
            'Oro\Component\Messaging\Transport\Null\NullSession'
        );
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new NullSession();
    }

    public function testShouldAllowCreateMessageWithoutAnyArguments()
    {
        $session = new NullSession();

        $message = $session->createMessage();

        $this->assertInstanceOf('Oro\Component\Messaging\Transport\Null\NullMessage', $message);

        $this->assertSame(null, $message->getBody());
        $this->assertSame([], $message->getHeaders());
        $this->assertSame([], $message->getProperties());
    }

    public function testShouldAllowCreateCustomMessage()
    {
        $session = new NullSession();

        $message = $session->createMessage('theBody', ['theProperty'], ['theHeader']);

        $this->assertInstanceOf('Oro\Component\Messaging\Transport\Null\NullMessage', $message);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['theProperty'], $message->getProperties());
        $this->assertSame(['theHeader'], $message->getHeaders());
    }

    public function testShouldAllowCreateQueue()
    {
        $session = new NullSession();

        $queue = $session->createQueue('aName');
        
        $this->assertInstanceOf('Oro\Component\Messaging\Transport\Null\NullQueue', $queue);
    }

    public function testShouldAllowCreateTopic()
    {
        $session = new NullSession();

        $topic = $session->createTopic('aName');

        $this->assertInstanceOf('Oro\Component\Messaging\Transport\Null\NullTopic', $topic);
    }

    public function testShouldAllowCreateConsumerForGivenQueue()
    {
        $session = new NullSession();

        $queue = new NullQueue('aName');

        $consumer = $session->createConsumer($queue);

        $this->assertInstanceOf('Oro\Component\Messaging\Transport\Null\NullMessageConsumer', $consumer);
    }

    public function testShouldAllowCreateProducer()
    {
        $session = new NullSession();

        $producer = $session->createProducer();

        $this->assertInstanceOf('Oro\Component\Messaging\Transport\Null\NullMessageProducer', $producer);
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
