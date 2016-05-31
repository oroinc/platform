<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;

class AmqpMessageTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageInterface()
    {
        $this->assertClassImplements(MessageInterface::class, AmqpMessage::class);
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

    public function testShouldNewMessageReturnEmptyLocalProperties()
    {
        $message = new AmqpMessage();

        $this->assertSame([], $message->getLocalProperties());
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

    public function testShouldAllowGetByNamePreviouslySetHeader()
    {
        $message = new AmqpMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame('fooVal', $message->getHeader('foo'));
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

    public function testShouldReturnDefaultIfPropertyNotSet()
    {
        $message = new AmqpMessage();

        $message->setProperties(['foo' => 'fooVal']);

        $this->assertSame('barDefault', $message->getProperty('bar', 'barDefault'));
    }

    public function testShouldAllowGetPreviouslySetLocalProperties()
    {
        $message = new AmqpMessage();

        $message->setLocalProperties(['foo' => 'fooVal']);

        $this->assertSame(['foo' => 'fooVal'], $message->getLocalProperties());
    }

    public function testShouldAllowGetByNamePreviouslySetLocalProperty()
    {
        $message = new AmqpMessage();

        $message->setLocalProperties(['foo' => 'fooVal']);

        $this->assertSame('fooVal', $message->getLocalProperty('foo'));
    }

    public function testShouldReturnDefaultIfLocalPropertyNotSet()
    {
        $message = new AmqpMessage();

        $message->setLocalProperties(['foo' => 'fooVal']);

        $this->assertSame('barDefault', $message->getLocalProperty('bar', 'barDefault'));
    }

    public function testShouldReturnDefaultIfHeaderNotSet()
    {
        $message = new AmqpMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame('barDefault', $message->getHeader('bar', 'barDefault'));
    }

    public function testShouldSetNullDeliveryKeyInConstructor()
    {
        $message = new AmqpMessage();

        $this->assertSame(null, $message->getDeliveryTag());
    }

    public function testShouldAllowGetPreviouslySetRoutingKey()
    {
        $message = new AmqpMessage();
        $message->setDeliveryTag('theDeliveryKey');

        $this->assertEquals('theDeliveryKey', $message->getDeliveryTag());
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
