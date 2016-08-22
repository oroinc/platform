<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\Testing\ClassExtensionTrait;

class DbalMessageTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageInterface()
    {
        $this->assertClassImplements(MessageInterface::class, DbalMessage::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new DbalMessage();
    }

    public function testShouldNewMessageReturnEmptyBody()
    {
        $message = new DbalMessage();

        $this->assertSame(null, $message->getBody());
    }

    public function testShouldNewMessageReturnEmptyProperties()
    {
        $message = new DbalMessage();

        $this->assertSame([], $message->getProperties());
    }

    public function testShouldNewMessageReturnEmptyHeaders()
    {
        $message = new DbalMessage();

        $this->assertSame([], $message->getHeaders());
    }

    public function testShouldAllowGetPreviouslySetBody()
    {
        $message = new DbalMessage();

        $message->setBody('theBody');

        $this->assertSame('theBody', $message->getBody());
    }

    public function testShouldAllowGetPreviouslySetHeaders()
    {
        $message = new DbalMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame(['foo' => 'fooVal'], $message->getHeaders());
    }

    public function testShouldAllowGetPreviouslySetProperties()
    {
        $message = new DbalMessage();

        $message->setProperties(['foo' => 'fooVal']);

        $this->assertSame(['foo' => 'fooVal'], $message->getProperties());
    }

    public function testShouldAllowGetByNamePreviouslySetProperty()
    {
        $message = new DbalMessage();

        $message->setProperties(['foo' => 'fooVal']);

        $this->assertSame('fooVal', $message->getProperty('foo'));
    }

    public function testShouldAllowGetByNamePreviouslySetHeader()
    {
        $message = new DbalMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame('fooVal', $message->getHeader('foo'));
    }

    public function testShouldReturnDefaultIfPropertyNotSet()
    {
        $message = new DbalMessage();

        $message->setProperties(['foo' => 'fooVal']);

        $this->assertSame('barDefault', $message->getProperty('bar', 'barDefault'));
    }

    public function testShouldReturnDefaultIfHeaderNotSet()
    {
        $message = new DbalMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame('barDefault', $message->getHeader('bar', 'barDefault'));
    }

    public function testShouldSetRedeliveredFalseInConstructor()
    {
        $message = new DbalMessage();

        $this->assertFalse($message->isRedelivered());
    }

    public function testShouldAllowGetPreviouslySetRedelivered()
    {
        $message = new DbalMessage();
        $message->setRedelivered(true);

        $this->assertTrue($message->isRedelivered());
    }

    public function testShouldReturnEmptyStringAsDefaultCorrelationId()
    {
        $message = new DbalMessage();

        self::assertSame('', $message->getCorrelationId());
    }

    public function testShouldAllowGetPreviouslySetCorrelationId()
    {
        $message = new DbalMessage();
        $message->setCorrelationId('theId');

        self::assertSame('theId', $message->getCorrelationId());
    }

    public function testShouldCastCorrelationIdToStringOnSet()
    {
        $message = new DbalMessage();
        $message->setCorrelationId(123);

        self::assertSame('123', $message->getCorrelationId());
    }

    public function testShouldReturnEmptyStringAsDefaultMessageId()
    {
        $message = new DbalMessage();

        self::assertSame('', $message->getMessageId());
    }

    public function testShouldAllowGetPreviouslySetMessageId()
    {
        $message = new DbalMessage();
        $message->setMessageId('theId');

        self::assertSame('theId', $message->getMessageId());
    }

    public function testShouldCastMessageIdToStringOnSet()
    {
        $message = new DbalMessage();
        $message->setMessageId(123);

        self::assertSame('123', $message->getMessageId());
    }

    public function testShouldReturnNullAsDefaultTimestamp()
    {
        $message = new DbalMessage();

        self::assertSame(null, $message->getTimestamp());
    }

    public function testShouldAllowGetPreviouslySetTimestamp()
    {
        $message = new DbalMessage();
        $message->setTimestamp(123);

        self::assertSame(123, $message->getTimestamp());
    }

    public function testShouldCastTimestampToIntOnSet()
    {
        $message = new DbalMessage();
        $message->setTimestamp('123');

        self::assertSame(123, $message->getTimestamp());
    }

    public function testShouldReturnNullAsDefaultReplyTo()
    {
        $message = new DbalMessage();

        self::assertSame(null, $message->getReplyTo());
    }

    public function testShouldAllowGetPreviouslySetReplyTo()
    {
        $message = new DbalMessage();
        $message->setReplyTo('theQueueName');

        self::assertSame('theQueueName', $message->getReplyTo());
    }
}
