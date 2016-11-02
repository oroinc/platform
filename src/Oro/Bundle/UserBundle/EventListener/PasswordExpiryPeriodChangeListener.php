<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\UserBundle\DependencyInjection\OroUserExtension;
use Oro\Bundle\UserBundle\Provider\PasswordChangePeriodConfigProvider;

class PasswordExpiryPeriodChangeListener
{
    const SETTING_VALUE   = 'password_change_period';
    const SETTING_UNIT    = 'password_change_period_unit';
    const SETTING_ENABLED = 'password_change_period_enabled';

    /** @var Registry */
    protected $registry;

    /** @var PasswordChangePeriodConfigProvider */
    protected $provider;

    /**
     * @param Registry $registry
     * @param PasswordChangePeriodConfigProvider $provider
     */
    public function __construct(Registry $registry, PasswordChangePeriodConfigProvider $provider)
    {
        $this->registry = $registry;
        $this->provider = $provider;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        $settingsUnitKey    = $this->getSettingsUnitKey();
        $settingsValueKey   = $this->getSettingsValueKey();
        $settingsEnabledKey = $this->getSettingsEnabledKey();

        $isSettingActive = $this->provider->isPasswordChangePeriodEnabled();
        $isSettingToggled = $event->isChanged($settingsEnabledKey);

        // check if the password period setting is active and if it has been toggled
        if (!$isSettingActive && !$isSettingToggled) {
            return;
        }

        if ($event->isChanged($settingsEnabledKey)
            || $event->isChanged($settingsUnitKey)
            || $event->isChanged($settingsValueKey)
        ) {
            $this->resetPasswordExpiryDates();
        }
    }

    /**
     * Sets a new password expiry date for all users.
     */
    protected function resetPasswordExpiryDates()
    {
        $newExpiryDate = $this->provider->getPasswordExpiryDateFromNow();
        $this->registry->getRepository('OroUserBundle:User')->updateAllUsersPasswordExpiration($newExpiryDate);
    }

    /**
     * @return string
     */
    protected function getSettingsEnabledKey()
    {
        return implode(ConfigManager::SECTION_MODEL_SEPARATOR, [OroUserExtension::ALIAS, self::SETTING_ENABLED]);
    }

    /**
     * @return string
     */
    protected function getSettingsUnitKey()
    {
        return implode(ConfigManager::SECTION_MODEL_SEPARATOR, [OroUserExtension::ALIAS, self::SETTING_UNIT]);
    }

    /**
     * @return string
     */
    protected function getSettingsValueKey()
    {
        return implode(ConfigManager::SECTION_MODEL_SEPARATOR, [OroUserExtension::ALIAS, self::SETTING_VALUE]);
    }
}
