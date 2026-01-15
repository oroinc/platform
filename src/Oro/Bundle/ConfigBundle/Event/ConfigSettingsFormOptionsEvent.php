<?php

namespace Oro\Bundle\ConfigBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * The event that is fired when options for a system configuration form are built.
 */
class ConfigSettingsFormOptionsEvent extends Event
{
    public const SET_OPTIONS = 'oro_config.settings_form_options_set';

    private string $scope;
    private array $allFormOptions;

    public function __construct(string $scope, array $allFormOptions)
    {
        $this->scope = $scope;
        $this->allFormOptions = $allFormOptions;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getAllFormOptions(): array
    {
        return $this->allFormOptions;
    }

    public function hasFormOptions(string $configKey): bool
    {
        return isset($this->allFormOptions[$configKey]);
    }

    public function unsetFormOptions(string $configKey): void
    {
        $this->assertKnownConfigKey($configKey);

        unset($this->allFormOptions[$configKey]);
    }

    private function assertKnownConfigKey(string $configKey): void
    {
        if (!isset($this->allFormOptions[$configKey])) {
            throw new \LogicException(sprintf('There are no form options for "%s".', $configKey));
        }
    }
}
