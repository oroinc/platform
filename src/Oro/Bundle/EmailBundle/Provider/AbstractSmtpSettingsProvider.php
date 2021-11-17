<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration as Config;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * This abstract class provides basic implementation of retrieving SMTP configuration from ORO config
 */
abstract class AbstractSmtpSettingsProvider implements SmtpSettingsAwareInterface
{
    protected ConfigManager $configManager;

    protected SymmetricCrypterInterface $encryptor;

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

    /**
     * @inheritdoc
     */
    abstract public function getSmtpSettings($scopeIdentifier = null): SmtpSettings;

    /**
     * @param null|int|object $scopeIdentifier
     *
     * @return SmtpSettings
     */
    protected function getConfigurationSmtpSettings($scopeIdentifier = null): SmtpSettings
    {
        if (!$this->applicationState->isInstalled()) {
            return new SmtpSettings();
        }

        $host = $this->configManager->get(
            Config::getConfigKeyByName(Config::KEY_SMTP_SETTINGS_HOST),
            false,
            false,
            $scopeIdentifier
        );
        $port = $this->configManager->get(
            Config::getConfigKeyByName(Config::KEY_SMTP_SETTINGS_PORT),
            false,
            false,
            $scopeIdentifier
        );
        $encryption = $this->configManager->get(
            Config::getConfigKeyByName(Config::KEY_SMTP_SETTINGS_ENC),
            false,
            false,
            $scopeIdentifier
        );
        $username = $this->configManager->get(
            Config::getConfigKeyByName(Config::KEY_SMTP_SETTINGS_USER),
            false,
            false,
            $scopeIdentifier
        );
        $password = $this->configManager->get(
            Config::getConfigKeyByName(Config::KEY_SMTP_SETTINGS_PASS),
            false,
            false,
            $scopeIdentifier
        );

        return new SmtpSettings(
            $host,
            $port,
            $encryption,
            $username,
            $this->encryptor->decryptData($password)
        );
    }
}
