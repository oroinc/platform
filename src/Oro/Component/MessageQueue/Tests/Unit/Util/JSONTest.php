<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Util\JSON;

class JSONTest extends \PHPUnit_Framework_TestCase
{
    public function testThrowIfMessageWithNoJsonContentType()
    {
        $message = new NullMessage();
        $message->setHeaders(['content_type' => 'foo']);
        $message->setBody('{}');

        $this->setExpectedException(InvalidMessageException::class, 'The message content type is not application/json');
        JSON::decodeMessage($message);

        $this->assertSame('{}', $message->getBody());
    }

    public function testShouldDecodeJsonMessageBody()
    {
        $message = new NullMessage();
        $message->setHeaders(['content_type' => 'application/json']);
        $message->setBody('{"foo": "fooVal"}');

        $decodedBody = JSON::decodeMessage($message);

        $this->assertSame('{"foo": "fooVal"}', $message->getBody());
        $this->assertSame(['foo' => 'fooVal'], $decodedBody);
    }

    public function testThrowIfMessageBodyNotValidJson()
    {
        $message = new NullMessage();
        $message->setHeaders(['content_type' => 'application/json']);
        $message->setBody('{]');

        $this->setExpectedException(
            InvalidMessageException::class,
            'The message content type is a json but the decoded content is not valid json.'
        );
        JSON::decodeMessage($message);
    }

    public function testShouldDecodeString()
    {
        $this->assertSame(['foo' => 'fooVal'], JSON::decode('{"foo": "fooVal"}'));
    }

    public function testThrowIfMalformedJson()
    {
        $this->setExpectedException(\InvalidArgumentException::class, 'The malformed json given. ');
        $this->assertSame(['foo' => 'fooVal'], JSON::decode('{]'));
    }
}
