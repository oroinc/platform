<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration as Config;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;

abstract class AbstractSmtpSettingsProvider implements SmtpSettingsProviderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var GlobalScopeManager */
    protected $globalScopeManager;

    /**
     * SmtpSettingsProvider constructor.
     *
     * @param ConfigManager      $configManager
     * @param GlobalScopeManager $globalScopeManager
     */
    public function __construct(
        ConfigManager $configManager,
        GlobalScopeManager $globalScopeManager
    ) {
        $this->configManager = $configManager;
        $this->globalScopeManager = $globalScopeManager;
    }

    /**
     * @inheritdoc
     */
    abstract public function getSmtpSettings($scopeIdentifier = null);

    /**
     * @return SmtpSettings
     */
    protected function getGlobalSmtpSettings()
    {
        return $this->getConfigurationSmtpSettings($this->globalScopeManager->getScopeId());
    }

    /**
     * @param null|int|object $scopeIdentifier
     *
     * @return SmtpSettings
     */
    protected function getConfigurationSmtpSettings($scopeIdentifier = null)
    {
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
        $encryption  = $this->configManager->get(
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
            $password
        );
    }
}
