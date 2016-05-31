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

        $this->assertSame(null, $message->getBody());
    }

    public function testShouldNewMessageReturnEmptyProperties()
    {
        $message = new NullMessage();

        $this->assertSame([], $message->getProperties());
    }

    public function testShouldNewMessageReturnEmptyLocalProperties()
    {
        $message = new NullMessage();

        $this->assertSame([], $message->getLocalProperties());
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

    public function testShouldAllowGetPreviouslySetLocalProperties()
    {
        $message = new NullMessage();

        $message->setLocalProperties(['foo' => 'fooVal']);

        $this->assertSame(['foo' => 'fooVal'], $message->getLocalProperties());
    }

    public function testShouldAllowGetByNamePreviouslySetLocalProperty()
    {
        $message = new NullMessage();

        $message->setLocalProperties(['foo' => 'fooVal']);

        $this->assertSame('fooVal', $message->getLocalProperty('foo'));
    }
    
    public function testShouldReturnDefaultIfLocalPropertyNotSet()
    {
        $message = new NullMessage();

        $message->setLocalProperties(['foo' => 'fooVal']);

        $this->assertSame('barDefault', $message->getLocalProperty('bar', 'barDefault'));
    }

    public function testShouldReturnDefaultIfHeaderNotSet()
    {
        $message = new NullMessage();

        $message->setHeaders(['foo' => 'fooVal']);

        $this->assertSame('barDefault', $message->getHeader('bar', 'barDefault'));
    }

    public function testShouldSetRedeliveredFalseInConstructor()
    {
        $message = new NullMessage();

        $this->assertFalse($message->isRedelivered());
    }

    public function testShouldAllowGetPreviouslySetRedelivered()
    {
        $message = new NullMessage();
        $message->setRedelivered(true);

        $this->assertTrue($message->isRedelivered());
    }
}
