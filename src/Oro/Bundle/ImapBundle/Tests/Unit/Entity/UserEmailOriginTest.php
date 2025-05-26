<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UserEmailOriginTest extends TestCase
{
    public function testGetId(): void
    {
        $origin = new UserEmailOrigin();
        ReflectionUtil::setId($origin, 123);
        $this->assertEquals(123, $origin->getId());
    }

    public function testImapHostGetterAndSetter(): void
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getImapHost());
        $origin->setImapHost('test');
        $this->assertEquals('test', $origin->getImapHost());
    }

    public function testImapPortGetterAndSetter(): void
    {
        $origin = new UserEmailOrigin();
        $this->assertEquals(null, $origin->getImapPort());
        $origin->setImapPort(123);
        $this->assertEquals(123, $origin->getImapPort());
    }

    public function testSslGetterAndSetter(): void
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getImapEncryption());
        $origin->setImapEncryption('test');
        $this->assertEquals('test', $origin->getImapEncryption());
    }

    public function testUserGetterAndSetter(): void
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getUser());
        $origin->setUser('test');
        $this->assertEquals('test', $origin->getUser());
    }

    public function testPasswordGetterAndSetter(): void
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getPassword());
        $origin->setPassword('test');
        $this->assertEquals('test', $origin->getPassword());
    }

    public function testSmtpHostGetterAndSetter(): void
    {
        $origin = new UserEmailOrigin();
        $this->assertNull($origin->getSmtpHost());
        $origin->setSmtpHost('test');
        $this->assertEquals('test', $origin->getSmtpHost());
    }

    public function testSmtpPortGetterAndSetter(): void
    {
        $origin = new UserEmailOrigin();
        $this->assertEquals(null, $origin->getSmtpPort());
        $origin->setSmtpPort(123);
        $this->assertEquals(123, $origin->getSmtpPort());
    }

    /**
     * @dataProvider setDataProviderSmtpConfiguredSuccess
     */
    public function testIsSmtpConfiguredSuccess(string $password, string $accessToken): void
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

    public function setDataProviderSmtpConfiguredSuccess(): array
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

    public function testIsSmtpConfiguredFailure(): void
    {
        $origin = new UserEmailOrigin();
        $origin->setSmtpHost('');
        $origin->setSmtpPort('');
        $origin->setUser('');
        $origin->setPassword('');
        $origin->setSmtpEncryption('');

        $this->assertFalse($origin->isSmtpConfigured());
    }

    public function testTypeGetterAndSetter(): void
    {
        $origin = new UserEmailOrigin();
        $this->assertEquals('other', $origin->getAccountType());
        $origin->setAccountType('test_type');
        $this->assertEquals('test_type', $origin->getAccountType());
    }

    /**
     * @dataProvider setDataProviderImapConfigured
     */
    public function testIsImapConfigured(
        ?string $host,
        ?int $port,
        ?string $user,
        string $password,
        string $accessToken,
        bool $expectedResult
    ): void {
        $origin = new UserEmailOrigin();
        $origin->setImapHost($host);
        $origin->setImapPort($port);
        $origin->setUser($user);
        $origin->setPassword($password);
        $origin->setAccessToken($accessToken);
        $origin->setSmtpEncryption('ssl');

        $this->assertEquals($expectedResult, $origin->isImapConfigured());
    }

    public function setDataProviderImapConfigured(): array
    {
        return [
            'empty host' => [
                'host' => null,
                'port' => 25,
                'user' => 'test',
                'password' => 'password',
                'accessToken' => 'token',
                'expectedResult' => false
            ],
            'empty port' => [
                'host' => 'host',
                'port' => null,
                'user' => 'test',
                'password' => 'password',
                'accessToken' => 'token',
                'expectedResult' => false
            ],
            'empty user' => [
                'host' => 'host',
                'port' => 25,
                'user' => null,
                'password' => 'password',
                'accessToken' => 'token',
                'expectedResult' => false
            ],
            'empty password and token' => [
                'host' => 'host',
                'port' => 25,
                'user' => 'test',
                'password' => '',
                'accessToken' => '',
                'expectedResult' => false
            ],
            'success with password' => [
                'host' => 'host',
                'port' => 25,
                'user' => 'test',
                'password' => 'password',
                'accessToken' => '',
                'expectedResult' => true
            ],
            'success with token' => [
                'host' => 'host',
                'port' => 25,
                'user' => 'test',
                'password' => '',
                'accessToken' => 'token',
                'expectedResult' => true
            ]
        ];
    }
}
