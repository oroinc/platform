<?php
namespace Oro\Component\Messaging\Tests\Transport\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use Oro\Component\Testing\ClassExtensionTrait;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;

class AmqpMessageTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\Message',
            'Oro\Component\Messaging\Transport\Amqp\AmqpMessage'
        );
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new AmqpMessage();
    }

    public function testShouldNewMessageReturnEmptyBody()
    {
        $message = new AmqpMessage();

        $this->assertSame(null, $message->getBody());
    }

    public function testShouldNewMessageReturnEmptyProperties()
    {
        $message = new AmqpMessage();

        $this->assertSame([], $message->getProperties());
    }

    public function testShouldNewMessageReturnEmptyHeaders()
    {
        $message = new AmqpMessage();

        $this->assertSame([], $message->getHeaders());
    }

    public function testShouldAllowGetPreviouslySetBody()
    {
        $message = new AmqpMessage();

        $message->setBody('theBody');

        $this->assertSame('theBody', $message->getBody());
    }

    public function testShouldAllowGetPreviouslySetHeaders()
    {
        $message = new AmqpMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame(['foo' => 'fooVal'], $message->getHeaders());
    }

    public function testShouldAllowGetPreviouslySetProperties()
    {
        $message = new AmqpMessage();

        $message->setProperties(['foo' => 'fooVal']);

        $this->assertSame(['foo' => 'fooVal'], $message->getProperties());
    }

    public function testShouldAllowGetByNamePreviouslySetProperty()
    {
        $message = new AmqpMessage();

        $message->setProperties(['foo' => 'fooVal']);

        $this->assertSame('fooVal', $message->getProperty('foo'));
    }

    public function testShouldAllowGetByNamePreviouslySetHeader()
    {
        $message = new AmqpMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame('fooVal', $message->getHeader('foo'));
    }

    public function testShouldReturnDefaultIfPropertyNotSet()
    {
        $message = new AmqpMessage();

        $message->setProperties(['foo' => 'fooVal']);

        $this->assertSame('barDefault', $message->getProperty('bar', 'barDefault'));
    }

    public function testShouldReturnDefaultIfHeaderNotSet()
    {
        $message = new AmqpMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame('barDefault', $message->getHeader('bar', 'barDefault'));
    }

    public function testShouldSetNullConsumerKeyInConstructor()
    {
        $message = new AmqpMessage();

        $this->assertSame(null, $message->getConsumerTag());
    }

    public function testShouldAllowGetPreviouslySetRoutingKey()
    {
        $message = new AmqpMessage();
        $message->setConsumerTag('theConsumerKey');

        $this->assertEquals('theConsumerKey', $message->getConsumerTag());
    }

    public function testShouldSetRedeliveredFalseInConstructor()
    {
        $message = new AmqpMessage();

        $this->assertFalse($message->isRedelivered());
    }

    public function testShouldAllowGetPreviouslySetRedelivered()
    {
        $message = new AmqpMessage();
        $message->setRedelivered(true);

        $this->assertTrue($message->isRedelivered());
    }
}
