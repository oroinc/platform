<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector;

use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use PHPUnit\Framework\TestCase;

class ImapConfigTest extends TestCase
{
    public function testConstructor(): void
    {
        $host = 'testHost';
        $port = 143;
        $ssl = 'ssl';
        $user = 'testUser';
        $password = 'testPwd';
        $token = 'testToken';
        $obj = new ImapConfig($host, $port, $ssl, $user, $password, $token);

        self::assertEquals($host, $obj->getHost());
        self::assertEquals($port, $obj->getPort());
        self::assertEquals($ssl, $obj->getSsl());
        self::assertEquals($user, $obj->getUser());
        self::assertEquals($password, $obj->getPassword());
        self::assertEquals($token, $obj->getAccessToken());
        self::assertNull($obj->getConnectionTimeout());
    }

    public function testSettersAndGetters(): void
    {
        $obj = new ImapConfig();

        self::assertNull($obj->getHost());
        self::assertNull($obj->getPort());
        self::assertNull($obj->getSsl());
        self::assertNull($obj->getUser());
        self::assertNull($obj->getPassword());
        self::assertNull($obj->getAccessToken());
        self::assertNull($obj->getConnectionTimeout());

        $host = 'testHost';
        $obj->setHost($host);
        self::assertEquals($host, $obj->getHost());
        $port = 143;
        $obj->setPort($port);
        self::assertEquals($port, $obj->getPort());
        $ssl = 'ssl';
        $obj->setSsl($ssl);
        self::assertEquals($ssl, $obj->getSsl());
        $user = 'testUser';
        $obj->setUser($user);
        self::assertEquals($user, $obj->getUser());
        $password = 'testPwd';
        $obj->setPassword($password);
        self::assertEquals($password, $obj->getPassword());
        $token = 'testToken';
        $obj->setAccessToken($token);
        self::assertEquals($token, $obj->getAccessToken());
        $connectionTimeout = 123;
        $obj->setConnectionTimeout($connectionTimeout);
        self::assertEquals($connectionTimeout, $obj->getConnectionTimeout());

        $obj->setHost(null);
        self::assertNull($obj->getHost());
        $obj->setPort(null);
        self::assertNull($obj->getPort());
        $obj->setSsl(null);
        self::assertNull($obj->getSsl());
        $obj->setUser(null);
        self::assertNull($obj->getUser());
        $obj->setPassword(null);
        self::assertNull($obj->getPassword());
        $obj->setAccessToken(null);
        self::assertNull($obj->getAccessToken());
        $obj->setConnectionTimeout(null);
        self::assertNull($obj->getConnectionTimeout());
    }
}
