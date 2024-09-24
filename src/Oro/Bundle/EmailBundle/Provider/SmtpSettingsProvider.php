<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Gets SMTP configuration settings from system config.
 */
class SmtpSettingsProvider implements SmtpSettingsProviderInterface
{
    private ConfigManager $configManager;
    private SymmetricCrypterInterface $encryptor;
    private ApplicationState $applicationState;

    public function __construct(
        ConfigManager $configManager,
        SymmetricCrypterInterface $encryptor,
        ApplicationState $applicationState
    ) {
        $this->configManager = $configManager;
        $this->encryptor = $encryptor;
        $this->applicationState = $applicationState;
    }

    #[\Override]
    public function getSmtpSettings(object|int|null $scopeIdentifier = null): SmtpSettings
    {
        if (!$this->applicationState->isInstalled()) {
            return new SmtpSettings();
        }

        return new SmtpSettings(
            $this->configManager->get('oro_email.smtp_settings_host', false, false, $scopeIdentifier),
            $this->configManager->get('oro_email.smtp_settings_port', false, false, $scopeIdentifier),
            $this->configManager->get('oro_email.smtp_settings_encryption', false, false, $scopeIdentifier),
            $this->configManager->get('oro_email.smtp_settings_username', false, false, $scopeIdentifier),
            $this->encryptor->decryptData(
                $this->configManager->get('oro_email.smtp_settings_password', false, false, $scopeIdentifier)
            )
        );
    }
}
