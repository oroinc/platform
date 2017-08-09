<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Null;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\Testing\ClassExtensionTrait;

class NullMessageTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageInterface()
    {
        $this->assertClassImplements(MessageInterface::class, NullMessage::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new NullMessage();
    }

    public function testShouldNewMessageReturnEmptyBody()
    {
        $message = new NullMessage();

        $this->assertNull($message->getBody());
    }

    public function testShouldNewMessageReturnEmptyProperties()
    {
        $message = new NullMessage();

        $this->assertSame([], $message->getProperties());
    }

    public function testShouldNewMessageReturnRedeliveredFalse()
    {
        $message = new NullMessage();

        $this->assertFalse($message->isRedelivered());
    }

    public function testShouldNewMessageReturnEmptyHeaders()
    {
        $message = new NullMessage();

        $this->assertSame([], $message->getHeaders());
    }

    public function testShouldAllowGetPreviouslySetBody()
    {
        $message = new NullMessage();

        $message->setBody('theBody');

        $this->assertSame('theBody', $message->getBody());
    }

    public function testShouldAllowGetPreviouslySetHeaders()
    {
        $message = new NullMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame(['foo' => 'fooVal'], $message->getHeaders());
    }

    public function testShouldAllowGetPreviouslySetProperties()
    {
        $message = new NullMessage();

        $message->setProperties(['foo' => 'fooVal']);

        $this->assertSame(['foo' => 'fooVal'], $message->getProperties());
    }

    public function testShouldAllowGetByNamePreviouslySetProperty()
    {
        $message = new NullMessage();

        $message->setProperties(['foo' => 'fooVal']);

        $this->assertSame('fooVal', $message->getProperty('foo'));
    }

    public function testShouldAllowGetByNamePreviouslySetHeader()
    {
        $message = new NullMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame('fooVal', $message->getHeader('foo'));
    }

    public function testShouldReturnDefaultIfPropertyNotSet()
    {
        $message = new NullMessage();

        $message->setProperties(['foo' => 'fooVal']);

        $this->assertSame('barDefault', $message->getProperty('bar', 'barDefault'));
    }

    public function testShouldReturnDefaultIfHeaderNotSet()
    {
        $message = new NullMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame('barDefault', $message->getHeader('bar', 'barDefault'));
    }

    public function testShouldAllowGetPreviouslySetRedelivered()
    {
        $message = new NullMessage();
        $message->setRedelivered(true);

        $this->assertTrue($message->isRedelivered());
    }

    public function testShouldReturnEmptyStringAsDefaultCorrelationId()
    {
        $message = new NullMessage();

        self::assertSame('', $message->getCorrelationId());
    }

    public function testShouldAllowGetPreviouslySetCorrelationId()
    {
        $message = new NullMessage();
        $message->setCorrelationId('theId');

        self::assertSame('theId', $message->getCorrelationId());
    }

    public function testShouldCastCorrelationIdToStringOnSet()
    {
        $message = new NullMessage();
        $message->setCorrelationId(123);

        self::assertSame('123', $message->getCorrelationId());
    }

    public function testShouldReturnEmptyStringAsDefaultMessageId()
    {
        $message = new NullMessage();

        self::assertSame('', $message->getMessageId());
    }

    public function testShouldAllowGetPreviouslySetMessageId()
    {
        $message = new NullMessage();
        $message->setMessageId('theId');

        self::assertSame('theId', $message->getMessageId());
    }

    public function testShouldCastMessageIdToStringOnSet()
    {
        $message = new NullMessage();
        $message->setMessageId(123);

        self::assertSame('123', $message->getMessageId());
    }

    public function testShouldReturnNullAsDefaultTimestamp()
    {
        $message = new NullMessage();

        self::assertSame(null, $message->getTimestamp());
    }

    public function testShouldAllowGetPreviouslySetTimestamp()
    {
        $message = new NullMessage();
        $message->setTimestamp(123);

        self::assertSame(123, $message->getTimestamp());
    }

    public function testShouldCastTimestampToIntOnSet()
    {
        $message = new NullMessage();
        $message->setTimestamp('123');

        self::assertSame(123, $message->getTimestamp());
    }

    public function testClone()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setProperties(['foo' => 'fooVal']);
        $message->setHeaders(['header' => 'headerVal']);
        $message->setRedelivered(true);

        $clonedMessage = clone $message;

        self::assertNotSame($message, $clonedMessage);
        self::assertSame($message->getBody(), $clonedMessage->getBody());
        self::assertCount(1, $clonedMessage->getProperties());
        self::assertSame($message->getProperty('foo'), $clonedMessage->getProperty('foo'));
        self::assertCount(1, $clonedMessage->getHeaders());
        self::assertSame($message->getHeader('header'), $clonedMessage->getHeader('header'));
        self::assertSame($message->isRedelivered(), $clonedMessage->isRedelivered());
    }
}
