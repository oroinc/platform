<?php

namespace Oro\Bundle\ConfigBundle\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The event that is fired when options for a system configuration form are built.
 */
class ConfigSettingsFormOptionsEvent extends Event
{
    public const SET_OPTIONS = 'oro_config.settings_form_options_set';

    private ConfigManager $configManager;
    private array $allFormOptions;

    public function __construct(ConfigManager $configManager, array $allFormOptions)
    {
        $this->configManager = $configManager;
        $this->allFormOptions = $allFormOptions;
    }

    public function getConfigManager(): ConfigManager
    {
        return $this->configManager;
    }

    /**
     * @return array [config key => form options, ...]
     */
    public function getAllFormOptions(): array
    {
        return $this->allFormOptions;
    }

    public function hasFormOptions(string $configKey): bool
    {
        return isset($this->allFormOptions[$configKey]);
    }

    public function getFormOptions(string $configKey): array
    {
        $this->assertKnownConfigKey($configKey);

        return $this->allFormOptions[$configKey];
    }

    public function setFormOptions(string $configKey, array $options): void
    {
        $this->assertKnownConfigKey($configKey);

        $this->allFormOptions[$configKey] = $options;
    }

    private function assertKnownConfigKey(string $configKey): void
    {
        if (!isset($this->allFormOptions[$configKey])) {
            throw new \LogicException(sprintf('There are no form options for "%s".', $configKey));
        }
    }
}
