<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector;

use Oro\Bundle\ImapBundle\Connector\ImapConfig;

class ImapConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $host = 'testHost';
        $port = 'testPort';
        $ssl = 'testSsl';
        $user = 'testUser';
        $password = 'testPwd';
        $token = 'testToken';
        $obj = new ImapConfig($host, $port, $ssl, $user, $password, $token);

        $this->assertEquals($host, $obj->getHost());
        $this->assertEquals($port, $obj->getPort());
        $this->assertEquals($ssl, $obj->getSsl());
        $this->assertEquals($user, $obj->getUser());
        $this->assertEquals($password, $obj->getPassword());
        $this->assertEquals($token, $obj->getAccessToken());
        $this->assertNull($obj->getConnectionTimeout());
    }

    public function testSettersAndGetters()
    {
        $obj = new ImapConfig();

        $host = 'testHost';
        $port = 'testPort';
        $ssl = 'testSsl';
        $user = 'testUser';
        $password = 'testPwd';
        $token = 'testToken';
        $connectionTimeout = 123;

        $obj->setHost($host);
        $obj->setPort($port);
        $obj->setSsl($ssl);
        $obj->setUser($user);
        $obj->setPassword($password);
        $obj->setAccessToken($token);
        $obj->setConnectionTimeout($connectionTimeout);

        $this->assertEquals($host, $obj->getHost());
        $this->assertEquals($port, $obj->getPort());
        $this->assertEquals($ssl, $obj->getSsl());
        $this->assertEquals($user, $obj->getUser());
        $this->assertEquals($password, $obj->getPassword());
        $this->assertEquals($token, $obj->getAccessToken());
        $this->assertEquals($connectionTimeout, $obj->getConnectionTimeout());

        $obj->setConnectionTimeout(null);
        $this->assertNull($obj->getConnectionTimeout());
    }
}
