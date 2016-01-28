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

    public function testImapHostGetterAndSetter()
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getImapHost());
        $origin->setImapHost('test');
        $this->assertEquals('test', $origin->getImapHost());
    }

    public function testImapPortGetterAndSetter()
    {
        $origin = new UserEmailOrigin();
        $this->assertEquals(null, $origin->getImapPort());
        $origin->setImapPort(123);
        $this->assertEquals(123, $origin->getImapPort());
    }

    public function testSslGetterAndSetter()
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getImapEncryption());
        $origin->setImapEncryption('test');
        $this->assertEquals('test', $origin->getImapEncryption());
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
        $this->assertEquals(null, $origin->getSmtpPort());
        $origin->setSmtpPort(123);
        $this->assertEquals(123, $origin->getSmtpPort());
    }

    /**
     * @param string $password
     * @param string $accessToken
     *
     * @dataProvider setDataProviderSmtpConfiguredSuccess
     */
    public function testIsSmtpConfiguredSuccess($password, $accessToken)
    {
        $origin = new UserEmailOrigin();
        $origin->setSmtpHost('host');
        $origin->setSmtpPort(25);
        $origin->setUser('test');
        $origin->setPassword($password);
        $origin->setAccessToken($accessToken);
        $origin->setSmtpEncryption('ssl');

        $this->assertTrue($origin->isSmtpConfigured());
    }

    /**
     * @return array
     */
    public function setDataProviderSmtpConfiguredSuccess()
    {
        return [
            'empty token' => [
                'password' => 'password',
                'accessToken' => ''
            ],
            'empty password' => [
                'password' => '',
                'accessToken' => 'token'
            ]
        ];
    }

    public function testIsSmtpConfiguredFailure()
    {
        $origin = new UserEmailOrigin();
        $origin->setSmtpHost('');
        $origin->setSmtpPort('');
        $origin->setUser('');
        $origin->setPassword('');
        $origin->setSmtpEncryption('');

        $this->assertFalse($origin->isSmtpConfigured());
    }
}
