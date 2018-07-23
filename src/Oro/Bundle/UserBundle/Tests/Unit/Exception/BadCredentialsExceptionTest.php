<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Exception;

use Oro\Bundle\UserBundle\Exception\BadCredentialsException;

class BadCredentialsExceptionTest extends \PHPUnit\Framework\TestCase
{
    /** @var BadCredentialsException */
    protected $exception;

    protected function setUp()
    {
        $this->exception = new BadCredentialsException();
    }

    public function testMessageKey()
    {
        $this->assertNull($this->exception->getMessageKey());

        $this->exception->setMessageKey('test.message.key');

        $this->assertEquals('test.message.key', $this->exception->getMessageKey());
    }

    public function testSerialize()
    {
        $this->exception->setMessageKey('test.message.key');

        $string = $this->exception->serialize();

        $exception = new BadCredentialsException();
        $exception->unserialize($string);

        $this->assertEquals($this->exception, $exception);
    }
}
