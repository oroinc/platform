<?php

namespace Oro\Bundle\ConfigBundle\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The event that is fired when system configuration form data are retrieved and before they are saved.
 */
class ConfigSettingsUpdateEvent extends Event
{
    public const FORM_PRESET = 'oro_config.settings_form_preset';
    public const BEFORE_SAVE = 'oro_config.settings_before_save';

    private ConfigManager $configManager;
    private array $settings;

    public function __construct(ConfigManager $configManager, array $settings)
    {
        $this->configManager = $configManager;
        $this->settings = $settings;
    }

    public function getConfigManager(): ConfigManager
    {
        return $this->configManager;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }
}
