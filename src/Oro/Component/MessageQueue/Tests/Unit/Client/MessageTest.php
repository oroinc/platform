<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\Message;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MessageTest extends TestCase
{
    public function testCouldBeConstructedWithoutAnyArguments(): void
    {
        new Message();
    }

    public function testCouldBeSetBodyAndPriorityViaConstructor(): void
    {
        $message = new Message('theBody', 'thePriority');
        self::assertEquals('theBody', $message->getBody());
        self::assertEquals('thePriority', $message->getPriority());
    }

    public function testShouldAllowGetPreviouslySetBody(): void
    {
        $message = new Message();

        self::assertSame($message, $message->setBody('theBody'));

        self::assertSame('theBody', $message->getBody());
    }

    public function testShouldAllowGetPreviouslySetContentType(): void
    {
        $message = new Message();

        self::assertSame($message, $message->setContentType('theContentType'));

        self::assertSame('theContentType', $message->getContentType());
    }

    public function testShouldAllowGetPreviouslySetDelay(): void
    {
        $message = new Message();

        self::assertSame($message, $message->setDelay('theDelay'));

        self::assertSame('theDelay', $message->getDelay());
    }

    public function testShouldAllowGetPreviouslySetExpire(): void
    {
        $message = new Message();

        self::assertSame($message, $message->setExpire('theExpire'));

        self::assertSame('theExpire', $message->getExpire());
    }

    public function testShouldAllowGetPreviouslySetPriority(): void
    {
        $message = new Message();

        self::assertSame($message, $message->setPriority('thePriority'));

        self::assertSame('thePriority', $message->getPriority());
    }

    public function testShouldAllowGetPreviouslySetMessageId(): void
    {
        $message = new Message();

        self::assertSame($message, $message->setMessageId('theMessageId'));

        self::assertSame('theMessageId', $message->getMessageId());
    }

    public function testShouldAllowGetPreviouslySetTimestamp(): void
    {
        $message = new Message();

        self::assertSame($message, $message->setTimestamp('theTimestamp'));

        self::assertSame('theTimestamp', $message->getTimestamp());
    }

    public function testShouldSetEmptyArrayAsDefaultHeadersInConstructor(): void
    {
        $message = new Message();

        self::assertSame([], $message->getHeaders());
    }

    public function testShouldAllowGetPreviouslySetHeaders(): void
    {
        $message = new Message();

        self::assertSame($message, $message->setHeaders(['foo' => 'fooVal']));

        self::assertSame(['foo' => 'fooVal'], $message->getHeaders());
    }

    public function testShouldAllowGetPreviouslySetHeader(): void
    {
        $message = new Message();

        self::assertSame($message, $message->setHeader('foo', 'fooVal'));

        self::assertSame('fooVal', $message->getHeader('foo'));
    }

    public function testShouldReturnDefaultIfHeaderNotSet(): void
    {
        $message = new Message();

        self::assertSame('theDefault', $message->getHeader('foo', 'theDefault'));
    }

    public function testShouldSetEmptyArrayAsDefaultPropertiesInConstructor(): void
    {
        $message = new Message();

        self::assertSame([], $message->getProperties());
    }

    public function testShouldAllowGetPreviouslySetProperties(): void
    {
        $message = new Message();

        self::assertSame($message, $message->setProperties(['foo' => 'fooVal']));

        self::assertSame(['foo' => 'fooVal'], $message->getProperties());
    }

    public function testShouldAllowGetPreviouslySetProperty(): void
    {
        $message = new Message();

        self::assertSame($message, $message->setProperty('foo', 'fooVal'));

        self::assertSame('fooVal', $message->getProperty('foo'));
    }

    public function testShouldReturnDefaultIfPropertyNotSet(): void
    {
        $message = new Message();

        self::assertSame('theDefault', $message->getProperty('foo', 'theDefault'));
    }
}
