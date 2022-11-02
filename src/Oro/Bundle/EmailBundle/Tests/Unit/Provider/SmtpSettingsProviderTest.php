<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Provider\SmtpSettingsProvider;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class SmtpSettingsProviderTest extends \PHPUnit\Framework\TestCase
{
    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $manager;

    private SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject $encryptor;

    private ApplicationState|\PHPUnit\Framework\MockObject\MockObject $applicationState;

    private SmtpSettingsProvider $provider;

    protected function setUp(): void
    {
        $this->encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $this->manager = $this->createMock(ConfigManager::class);
        $this->applicationState = $this->createMock(ApplicationState::class);

        $this->provider = new SmtpSettingsProvider($this->manager, $this->encryptor, $this->applicationState);
    }

    public function testGetSmtpSettingsNotInstalled(): void
    {
        $this->applicationState
            ->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $this->manager
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals(new SmtpSettings(), $this->provider->getSmtpSettings());
    }

    public function testGetSmtpSettings(): void
    {
        $this->applicationState
            ->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $this->encryptor
            ->expects(self::once())
            ->method('decryptData')
            ->with('pass')
            ->willReturn('pass_decrypted');

        $this->manager
            ->expects(self::exactly(5))
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_email.smtp_settings_host', false, false, null, 'example.org'],
                    ['oro_email.smtp_settings_port', false, false, null, 465],
                    ['oro_email.smtp_settings_encryption', false, false, null, 'ssl'],
                    ['oro_email.smtp_settings_username', false, false, null, 'user'],
                    ['oro_email.smtp_settings_password', false, false, null, 'pass'],
                ]
            );

        $smtpSettings = $this->provider->getSmtpSettings();

        self::assertSame('example.org', $smtpSettings->getHost());
        self::assertSame(465, $smtpSettings->getPort());
        self::assertSame('ssl', $smtpSettings->getEncryption());
        self::assertSame('user', $smtpSettings->getUsername());
        self::assertSame('pass_decrypted', $smtpSettings->getPassword());
    }

    public function testGetSmtpSettingsWithScopeIdentifier(): void
    {
        $this->applicationState
            ->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $this->encryptor
            ->expects(self::once())
            ->method('decryptData')
            ->with('pass')
            ->willReturn('pass_decrypted');

        $scopeIdentifier = new \stdClass();
        $this->manager
            ->expects(self::exactly(5))
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_email.smtp_settings_host', false, false, $scopeIdentifier, 'example.org'],
                    ['oro_email.smtp_settings_port', false, false, $scopeIdentifier, 465],
                    ['oro_email.smtp_settings_encryption', false, false, $scopeIdentifier, 'ssl'],
                    ['oro_email.smtp_settings_username', false, false, $scopeIdentifier, 'user'],
                    ['oro_email.smtp_settings_password', false, false, $scopeIdentifier, 'pass'],
                ]
            );

        $smtpSettings = $this->provider->getSmtpSettings($scopeIdentifier);

        self::assertSame('example.org', $smtpSettings->getHost());
        self::assertSame(465, $smtpSettings->getPort());
        self::assertSame('ssl', $smtpSettings->getEncryption());
        self::assertSame('user', $smtpSettings->getUsername());
        self::assertSame('pass_decrypted', $smtpSettings->getPassword());
    }
}
