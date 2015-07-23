<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class UserEmailOriginTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $origin = new UserEmailOrigin();
        ReflectionUtil::setId($origin, 123);
        $this->assertEquals(123, $origin->getId());
    }

    public function testHostGetterAndSetter()
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getHost());
        $origin->setHost('test');
        $this->assertEquals('test', $origin->getHost());
    }

    public function testPortGetterAndSetter()
    {
        $origin = new UserEmailOrigin();
        $this->assertEquals(0, $origin->getPort());
        $origin->setPort(123);
        $this->assertEquals(123, $origin->getPort());
    }

    public function testSslGetterAndSetter()
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getSsl());
        $origin->setSsl('test');
        $this->assertEquals('test', $origin->getSsl());
    }

    public function testUserGetterAndSetter()
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getUser());
        $origin->setUser('test');
        $this->assertEquals('test', $origin->getUser());
    }

    public function testPasswordGetterAndSetter()
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getPassword());
        $origin->setPassword('test');
        $this->assertEquals('test', $origin->getPassword());
    }

    public function testSmtpHostGetterAndSetter()
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getSmtpHost());
        $origin->setSmtpHost('test');
        $this->assertEquals('test', $origin->getSmtpHost());
    }

    public function testSmtpPortGetterAndSetter()
    {
        $origin = new UserEmailOrigin();
        $this->assertEquals(0, $origin->getSmtpPort());
        $origin->setSmtpPort(123);
        $this->assertEquals(123, $origin->getSmtpPort());
    }
}
