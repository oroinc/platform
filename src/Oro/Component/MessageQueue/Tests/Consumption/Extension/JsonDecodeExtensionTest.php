<?php
namespace Oro\Component\MessageQueue\Tests\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension;
use Oro\Component\MessageQueue\Consumption\Extension\JsonDecodeExtension;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class JsonDecodeExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(Extension::class, JsonDecodeExtension::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new JsonDecodeExtension();
    }

    public function testShouldIgnoreMessageWithNoJsonContentType()
    {
        $message = new NullMessage();
        $message->setHeaders(['content_type' => 'foo']);
        $message->setBody('{}');

        $extension = new JsonDecodeExtension();
        $extension->onPreReceived($this->createContextStub($message));

        $this->assertSame('{}', $message->getBody());
    }

    public function testShouldDecodeJsonMessageBodyAndSetItToLocalProperty()
    {
        $message = new NullMessage();
        $message->setHeaders(['content_type' => 'application/json']);
        $message->setBody('{"foo": "fooVal"}');

        $extension = new JsonDecodeExtension();
        $extension->onPreReceived($this->createContextStub($message));

        $this->assertSame('{"foo": "fooVal"}', $message->getBody());
        $this->assertSame(['foo' => 'fooVal'], $message->getLocalProperty('json_body'));
    }

    public function testThrowIfMessageBodyNotValidJson()
    {
        $message = new NullMessage();
        $message->setHeaders(['content_type' => 'application/json']);
        $message->setBody('{]');

        $extension = new JsonDecodeExtension();

        $this->setExpectedException(
            InvalidMessageException::class,
            'The message content type is a json but the body is not valid json. Code: 4'
        );
        $extension->onPreReceived($this->createContextStub($message));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    protected function createContextStub($message = null)
    {
        $sessionMock = $this->getMock(Context::class, [], [], '', false);
        $sessionMock
            ->expects($this->any())
            ->method('getMessage')
            ->willReturn($message)
        ;
        $sessionMock
            ->expects($this->any())
            ->method('getLogger')
            ->willReturn(new NullLogger())
        ;

        return $sessionMock;
    }
}
