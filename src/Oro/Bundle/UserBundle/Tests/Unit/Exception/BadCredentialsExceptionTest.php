<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Exception;

use Oro\Bundle\UserBundle\Exception\BadCredentialsException;

class BadCredentialsExceptionTest extends \PHPUnit\Framework\TestCase
{
    private $exception;

    protected function setUp(): void
    {
        $this->exception = new BadCredentialsException();
    }

    public function testMessageKey(): void
    {
        self::assertNull($this->exception->getMessageKey());

        $this->exception->setMessageKey('test.message.key');

        self::assertEquals('test.message.key', $this->exception->getMessageKey());
    }

    public function testSerialize(): void
    {
        $this->exception->setMessageKey('test.message.key');

        $string = serialize($this->exception);

        $exception = unserialize($string);

        self::assertEquals($this->exception, $exception);
    }
}
