<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Model;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SmtpSettingsFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testWithoutRequestValues(): void
    {
        $smtpSettings = new SmtpSettings();

        self::assertEquals($smtpSettings, SmtpSettingsFactory::createFromRequest(new Request()));
    }

    /**
     * @dataProvider validParametersDataProvider
     */
    public function testWithValidRequestValues(string $uri, string $method, array $parameters): void
    {
        $request = Request::create($uri, $method, $parameters);

        $smtpSettings = new SmtpSettings();
        $factorySmtpSettings = SmtpSettingsFactory::createFromRequest($request);

        self::assertNotSame($smtpSettings->getHost(), $factorySmtpSettings->getHost());
        self::assertNotSame($smtpSettings->getPort(), $factorySmtpSettings->getPort());
        self::assertNotSame($smtpSettings->getEncryption(), $factorySmtpSettings->getEncryption());
        self::assertNotSame($smtpSettings->getUsername(), $factorySmtpSettings->getUsername());
        self::assertNotSame($smtpSettings->getPassword(), $factorySmtpSettings->getPassword());
        self::assertTrue($factorySmtpSettings->isEligible());
    }

    /**
     * @dataProvider partialParametersDataProvider
     */
    public function testWithPartialValidRequestValues(string $uri, string $method, array $parameters): void
    {
        $request = Request::create($uri, $method, $parameters);

        $smtpSettings = new SmtpSettings();
        $factorySmtpSettings = SmtpSettingsFactory::createFromRequest($request);

        self::assertNotSame($smtpSettings->getHost(), $factorySmtpSettings->getHost());
        self::assertNotSame($smtpSettings->getPort(), $factorySmtpSettings->getPort());
        self::assertNotSame($smtpSettings->getEncryption(), $factorySmtpSettings->getEncryption());
        self::assertSame($smtpSettings->getUsername(), $factorySmtpSettings->getUsername());
        self::assertSame($smtpSettings->getPassword(), $factorySmtpSettings->getPassword());
        self::assertTrue($factorySmtpSettings->isEligible());
    }

    /**
     * @dataProvider invalidParametersDataProvider
     */
    public function testWithInvalidRequestValues(string $uri, string $method, array $parameters): void
    {
        $request = Request::create($uri, $method, $parameters);
        $factorySmtpSettings = SmtpSettingsFactory::createFromRequest($request);

        self::assertFalse($factorySmtpSettings->isEligible());
    }

    public function validParametersDataProvider(): array
    {
        return [
            [
                'uri' => 'http://localhost',
                'method' => 'GET',
                'parameters' => [
                    'host' => 'smtp.orocrm.com',
                    'port' => '465',
                    'encryption' => 'ssl',
                    'username' => 'some_user',
                    'password' => 'some_pass',
                ]
            ],
            [
                'uri' => 'http://localhost',
                'method' => 'POST',
                'parameters' => [
                    'host' => 'smtp.orocrm.com',
                    'port' => '587',
                    'encryption' => 'tls',
                    'username' => 'some_user',
                    'password' => 'some_pass',
                ]
            ],
        ];
    }

    public function partialParametersDataProvider(): array
    {
        return [
            [
                'uri' => 'http://localhost',
                'method' => 'GET',
                'parameters' => [
                    'host' => 'smtp.orocrm.com',
                    'port' => '465',
                    'encryption' => '',
                ]
            ],
            [
                'uri' => 'http://localhost',
                'method' => 'POST',
                'parameters' => [
                    'host' => 'smtp.orocrm.com',
                    'port' => '587',
                    'encryption' => 'tls',
                ]
            ],
        ];
    }

    public function invalidParametersDataProvider(): array
    {
        return [
            [
                'uri' => 'http://localhost',
                'method' => 'GET',
                'parameters' => [
                    'host' => '',
                    'port' => '',
                    'encryption' => '',
                ]
            ],
            [
                'uri' => 'http://localhost',
                'method' => 'POST',
                'parameters' => [
                    'host' => '',
                    'port' => '',
                    'encryption' => '',
                ]
            ],
        ];
    }

    public function testCreateFromUserEmailOriginWithEmptyUserEmailOrigin(): void
    {
        $encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $smtpSettingsFactory = new SmtpSettingsFactory($encryptor);
        $smtpSettings = $smtpSettingsFactory->createFromUserEmailOrigin(new UserEmailOrigin());

        self::assertEquals(new SmtpSettings(), $smtpSettings);
    }

    public function testCreateFromUserEmailOrigin(): void
    {
        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setSmtpHost('smtp.host');
        $userEmailOrigin->setSmtpPort(123);
        $userEmailOrigin->setSmtpEncryption('ssl');
        $userEmailOrigin->setUser('user');
        $userEmailOrigin->setPassword('encrypted_password');

        $encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $encryptor->expects(self::once())
            ->method('decryptData')
            ->with($userEmailOrigin->getPassword())
            ->willReturn('decrypted_password');

        $smtpSettingsFactory = new SmtpSettingsFactory($encryptor);

        $smtpSettings = $smtpSettingsFactory->createFromUserEmailOrigin($userEmailOrigin);
        $expectedSmtpSettings = new SmtpSettings('smtp.host', 123, 'ssl', 'user', 'decrypted_password');

        self::assertEquals($expectedSmtpSettings, $smtpSettings);
    }

    public function testCreateFromEmptyArray(): void
    {
        $encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $smtpSettingsFactory = new SmtpSettingsFactory($encryptor);
        $smtpSettings = $smtpSettingsFactory->createFromArray([]);

        self::assertEquals(new SmtpSettings(), $smtpSettings);
    }

    public function testCreateFromArray(): void
    {
        $encryptor = $this->createMock(SymmetricCrypterInterface::class);

        $data = [
            'smtp.host',
            123,
            'ssl',
            'user',
            'decrypted_password'
        ];

        $smtpSettingsFactory = new SmtpSettingsFactory($encryptor);
        $smtpSettings = $smtpSettingsFactory->createFromArray($data);
        $expectedSmtpSettings = new SmtpSettings('smtp.host', 123, 'ssl', 'user', 'decrypted_password');

        self::assertEquals($expectedSmtpSettings, $smtpSettings);
    }
}
