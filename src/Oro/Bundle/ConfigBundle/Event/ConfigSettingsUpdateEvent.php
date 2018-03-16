<?php

namespace Oro\Bundle\ConfigBundle\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\EventDispatcher\Event;

class ConfigSettingsUpdateEvent extends Event
{
    const FORM_PRESET = 'oro_config.settings_form_preset';
    const BEFORE_SAVE = 'oro_config.settings_before_save';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param ConfigManager $configManager
     * @param array $settings
     */
    public function __construct(ConfigManager $configManager, array $settings)
    {
        $this->configManager = $configManager;
        $this->settings = $settings;
    }

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     * @return ConfigSettingsUpdateEvent
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;

        return $this;
    }
}
