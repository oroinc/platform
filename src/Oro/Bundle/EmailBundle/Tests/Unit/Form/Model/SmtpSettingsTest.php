<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Model;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;

class SmtpSettingsTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaultSmtpSettings(): void
    {
        $smtpSettings = new SmtpSettings();

        self::assertNull($smtpSettings->getHost());
        self::assertNull($smtpSettings->getPort());
        self::assertNull($smtpSettings->getEncryption());
        self::assertNull($smtpSettings->getUsername());
        self::assertNull($smtpSettings->getPassword());
        self::assertFalse($smtpSettings->isEligible());
    }

    public function testGetters(): void
    {
        $smtpSettings = new SmtpSettings('example.org', 25, 'tls', 'sample_user', 'sample_password');

        self::assertEquals('example.org', $smtpSettings->getHost());
        self::assertEquals(25, $smtpSettings->getPort());
        self::assertEquals('tls', $smtpSettings->getEncryption());
        self::assertEquals('sample_user', $smtpSettings->getUsername());
        self::assertEquals('sample_password', $smtpSettings->getPassword());
        self::assertTrue($smtpSettings->isEligible());
    }

    public function testSetters(): void
    {
        $smtpSettings = (new SmtpSettings())
            ->setHost('example.org')
            ->setPort(25)
            ->setEncryption('tls')
            ->setUsername('sample_user')
            ->setPassword('sample_password');

        self::assertEquals('example.org', $smtpSettings->getHost());
        self::assertEquals(25, $smtpSettings->getPort());
        self::assertEquals('tls', $smtpSettings->getEncryption());
        self::assertEquals('sample_user', $smtpSettings->getUsername());
        self::assertEquals('sample_password', $smtpSettings->getPassword());
        self::assertTrue($smtpSettings->isEligible());
    }

    /**
     * @dataProvider isEligibleDataProvider
     */
    public function testIsEligible(
        ?string $host,
        string|int|null $port,
        ?string $encryption,
        ?string $user,
        ?string $password,
        bool $expected
    ): void {
        $smtpSettings = new SmtpSettings($host, $port, $encryption, $user, $password);

        self::assertSame($expected, $smtpSettings->isEligible());
    }

    public function isEligibleDataProvider(): array
    {
        return [
            'no host' => [
                'host' => null,
                'port' => null,
                'encryption' => null,
                'user' => null,
                'password' => null,
                'expected' => false,
            ],
            'no port' => [
                'host' => 'example.org',
                'port' => null,
                'encryption' => null,
                'user' => null,
                'password' => null,
                'expected' => false,
            ],
            'no encryption' => [
                'host' => 'example.org',
                'port' => 25,
                'encryption' => null,
                'user' => null,
                'password' => null,
                'expected' => false,
            ],
            'no user' => [
                'host' => 'example.org',
                'port' => 25,
                'encryption' => 'ssl',
                'user' => null,
                'password' => null,
                'expected' => true,
            ],
            'no password' => [
                'host' => 'example.org',
                'port' => 25,
                'encryption' => 'ssl',
                'user' => 'sample_user',
                'password' => null,
                'expected' => true,
            ],
            'with user and password' => [
                'host' => 'example.org',
                'port' => 25,
                'encryption' => 'ssl',
                'user' => 'sample_user',
                'password' => 'sample_password',
                'expected' => true,
            ],
            'empty host' => [
                'host' => '',
                'port' => 'invalid',
                'encryption' => 'ssl',
                'user' => 'sample_user',
                'password' => 'sample_password',
                'expected' => false,
            ],
            'invalid port' => [
                'host' => 'example.org',
                'port' => 'invalid',
                'encryption' => 'ssl',
                'user' => 'sample_user',
                'password' => 'sample_password',
                'expected' => false,
            ],
        ];
    }

    public function testToString(): void
    {
        self::assertEquals('', (string)(new SmtpSettings()));
    }
}
