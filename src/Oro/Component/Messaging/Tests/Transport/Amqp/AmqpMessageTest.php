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

    public function testShouldAllowGetInternalMessagePreviouslySet()
    {
        $message = new AmqpMessage();
        $internalMessage = new AMQPLibMessage('', []);

        $message->setInternalMessage($internalMessage);

        $this->assertSame($internalMessage, $message->getInternalMessage());
    }

    public function testShouldAllowUnsetInternalMessage()
    {
        $message = new AmqpMessage();

        $message->setInternalMessage(new AMQPLibMessage('', []));
        $message->setInternalMessage(null);

        $this->assertSame(null, $message->getInternalMessage());
    }
}
