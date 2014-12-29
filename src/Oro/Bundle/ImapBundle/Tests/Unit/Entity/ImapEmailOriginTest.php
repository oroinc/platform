<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;

class ImapEmailOriginTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $origin = new ImapEmailOrigin();
        ReflectionUtil::setId($origin, 123);
        $this->assertEquals(123, $origin->getId());
    }

    public function testHostGetterAndSetter()
    {
        $origin = new ImapEmailOrigin();
        $this->assertNull($origin->getHost());
        $origin->setHost('test');
        $this->assertEquals('test', $origin->getHost());
    }

    public function testPortGetterAndSetter()
    {
        $origin = new ImapEmailOrigin();
        $this->assertEquals(0, $origin->getPort());
        $origin->setPort(123);
        $this->assertEquals(123, $origin->getPort());
    }

    public function testSslGetterAndSetter()
    {
        $origin = new ImapEmailOrigin();
        $this->assertNull($origin->getSsl());
        $origin->setSsl('test');
        $this->assertEquals('test', $origin->getSsl());
    }

    public function testUserGetterAndSetter()
    {
        $origin = new ImapEmailOrigin();
        $this->assertNull($origin->getUser());
        $origin->setUser('test');
        $this->assertEquals('test', $origin->getUser());
    }

    public function testPasswordGetterAndSetter()
    {
        $origin = new ImapEmailOrigin();
        $this->assertNull($origin->getPassword());
        $origin->setPassword('test');
        $this->assertEquals('test', $origin->getPassword());
    }
}
