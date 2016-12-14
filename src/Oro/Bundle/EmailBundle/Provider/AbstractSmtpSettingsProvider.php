<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\AbstractScopeManager;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration as Config;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;

abstract class AbstractSmtpSettingsProvider
{
    /** @var GlobalScopeManager */
    protected $globalConfigManager;

    /**
     * SmtpSettingsProvider constructor.
     *
     * @param GlobalScopeManager $globalConfigManager
     */
    public function __construct(GlobalScopeManager $globalConfigManager)
    {
        $this->globalConfigManager = $globalConfigManager;
    }

    /**
     * @param string|null $scope
     *
     * @return SmtpSettings
     */
    abstract public function getSmtpSettings($scope = null);

    /**
     * @return SmtpSettings
     */
    protected function getGlobalSmtpSettings()
    {
        return $this->getConfigurationSmtpSettings($this->globalConfigManager);
    }

    /**
     * @param AbstractScopeManager $manager
     *
     * @return SmtpSettings
     */
    protected function getConfigurationSmtpSettings(AbstractScopeManager $manager)
    {
        $host = $manager->getSettingValue(
            Config::getConfigKeyByName(Config::KEY_SMTP_SETTINGS_HOST)
        );
        $port = $manager->getSettingValue(
            Config::getConfigKeyByName(Config::KEY_SMTP_SETTINGS_PORT)
        );
        $encryption  = $manager->getSettingValue(
            Config::getConfigKeyByName(Config::KEY_SMTP_SETTINGS_ENC)
        );
        $username = $manager->getSettingValue(
            Config::getConfigKeyByName(Config::KEY_SMTP_SETTINGS_USER)
        );
        $password = $manager->getSettingValue(
            Config::getConfigKeyByName(Config::KEY_SMTP_SETTINGS_PASS)
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
