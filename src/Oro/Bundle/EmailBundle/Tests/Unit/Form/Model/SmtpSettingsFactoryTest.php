<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration;
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
    public function testWithoutRequestValues()
    {
        $smtpSettings = new SmtpSettings();

        $this->assertEquals($smtpSettings, SmtpSettingsFactory::createFromRequest(new Request()));
    }

    /**
     * @dataProvider validParametersDataProvider
     *
     * @param string $uri
     * @param string $method
     * @param array  $parameters
     */
    public function testWithValidRequestValues($uri, $method, $parameters)
    {
        $request = Request::create($uri, $method, $parameters);

        $smtpSettings = new SmtpSettings();
        $factorySmtpSettings = SmtpSettingsFactory::createFromRequest($request);

        $this->assertNotSame($smtpSettings->getHost(), $factorySmtpSettings->getHost());
        $this->assertNotSame($smtpSettings->getPort(), $factorySmtpSettings->getPort());
        $this->assertNotSame($smtpSettings->getEncryption(), $factorySmtpSettings->getEncryption());
        $this->assertNotSame($smtpSettings->getUsername(), $factorySmtpSettings->getUsername());
        $this->assertNotSame($smtpSettings->getPassword(), $factorySmtpSettings->getPassword());
        $this->assertTrue($factorySmtpSettings->isEligible());
    }

    /**
     * @dataProvider partialParametersDataProvider
     *
     * @param string $uri
     * @param string $method
     * @param array  $parameters
     */
    public function testWithPartialValidRequestValues($uri, $method, $parameters)
    {
        $request = Request::create($uri, $method, $parameters);

        $smtpSettings = new SmtpSettings();
        $factorySmtpSettings = SmtpSettingsFactory::createFromRequest($request);

        $this->assertNotSame($smtpSettings->getHost(), $factorySmtpSettings->getHost());
        $this->assertNotSame($smtpSettings->getPort(), $factorySmtpSettings->getPort());
        $this->assertNotSame($smtpSettings->getEncryption(), $factorySmtpSettings->getEncryption());
        $this->assertSame($smtpSettings->getUsername(), $factorySmtpSettings->getUsername());
        $this->assertSame($smtpSettings->getPassword(), $factorySmtpSettings->getPassword());
        $this->assertTrue($factorySmtpSettings->isEligible());
    }

    /**
     * @dataProvider invalidParametersDataProvider
     *
     * @param string $uri
     * @param string $method
     * @param array  $parameters
     */
    public function testWithInvalidRequestValues($uri, $method, $parameters)
    {
        $request = Request::create($uri, $method, $parameters);
        $factorySmtpSettings = SmtpSettingsFactory::createFromRequest($request);

        $this->assertFalse($factorySmtpSettings->isEligible());
    }

    /**
     * @return array
     */
    public function validParametersDataProvider()
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

    /**
     * @return array
     */
    public function partialParametersDataProvider()
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

    /**
     * @return array
     */
    public function invalidParametersDataProvider()
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

    public function testCreateFromUserEmailOriginWithEmptyUserEmailOrigin()
    {
        /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject $encryptor */
        $encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $smtpSettingsFactory = new SmtpSettingsFactory($encryptor);
        $smtpSettings = $smtpSettingsFactory->createFromUserEmailOrigin(new UserEmailOrigin());

        $this->assertEquals(new SmtpSettings(), $smtpSettings);
    }

    public function testCreateFromUserEmailOrigin()
    {
        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setSmtpHost('smtp.host');
        $userEmailOrigin->setSmtpPort(123);
        $userEmailOrigin->setSmtpEncryption('ssl');
        $userEmailOrigin->setUser('user');
        $userEmailOrigin->setPassword('encrypted_password');

        /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject $encryptor */
        $encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $encryptor->expects($this->once())
            ->method('decryptData')
            ->with($userEmailOrigin->getPassword())
            ->willReturn('decrypted_password');

        $smtpSettingsFactory = new SmtpSettingsFactory($encryptor);

        $smtpSettings = $smtpSettingsFactory->create($userEmailOrigin);
        $expectedSmtpSettings = new SmtpSettings('smtp.host', 123, 'ssl', 'user', 'decrypted_password');

        $this->assertEquals($expectedSmtpSettings, $smtpSettings);
    }

    public function testCreateWithUnsupportedType()
    {
        $this->expectException(\InvalidArgumentException::class);
        /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject $encryptor */
        $encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $smtpSettingsFactory = new SmtpSettingsFactory($encryptor);

        $smtpSettingsFactory->create(new \stdClass());
    }

    public function testCreateFromEmptyArray()
    {
        /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject $encryptor */
        $encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $smtpSettingsFactory = new SmtpSettingsFactory($encryptor);
        $smtpSettings = $smtpSettingsFactory->create([]);

        $this->assertEquals(new SmtpSettings(), $smtpSettings);
    }

    public function testCreateFromArray()
    {
        $encryptedPassword = 'encrypted_password';

        /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject $encryptor */
        $encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $encryptor->expects($this->once())
            ->method('decryptData')
            ->with($encryptedPassword)
            ->willReturn('decrypted_password');

        $data = [
            Configuration::getConfigKeyByName(
                Configuration::KEY_SMTP_SETTINGS_HOST,
                ConfigManager::SECTION_VIEW_SEPARATOR
            ) => [ConfigManager::VALUE_KEY => 'smtp.host'],
            Configuration::getConfigKeyByName(
                Configuration::KEY_SMTP_SETTINGS_PORT,
                ConfigManager::SECTION_VIEW_SEPARATOR
            ) => [ConfigManager::VALUE_KEY => 123],
            Configuration::getConfigKeyByName(
                Configuration::KEY_SMTP_SETTINGS_ENC,
                ConfigManager::SECTION_VIEW_SEPARATOR
            ) => [ConfigManager::VALUE_KEY => 'ssl'],
            Configuration::getConfigKeyByName(
                Configuration::KEY_SMTP_SETTINGS_USER,
                ConfigManager::SECTION_VIEW_SEPARATOR
            ) => [ConfigManager::VALUE_KEY => 'user'],
            Configuration::getConfigKeyByName(
                Configuration::KEY_SMTP_SETTINGS_PASS,
                ConfigManager::SECTION_VIEW_SEPARATOR
            ) => [ConfigManager::VALUE_KEY => $encryptedPassword]
        ];

        $smtpSettingsFactory = new SmtpSettingsFactory($encryptor);
        $smtpSettings = $smtpSettingsFactory->create($data);
        $expectedSmtpSettings = new SmtpSettings('smtp.host', 123, 'ssl', 'user', 'decrypted_password');

        $this->assertEquals($expectedSmtpSettings, $smtpSettings);
    }
}
