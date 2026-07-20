<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Traits;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

trait ConfigManagerAwareTestTrait
{
    private array $configSnapshots = [];

    /**
     * @param string|null $scope The configuration scope (e.g.: global, organization, user, etc.)
     *                           or NULL to get the configuration manager for the current scope
     *
     * @return ConfigManager
     */
    protected static function getConfigManager(?string $scope = 'global'): ConfigManager
    {
        if (!$scope) {
            return self::getContainer()->get('oro_config.manager');
        }

        return self::getContainer()->get('oro_config.' . $scope);
    }

    /**
     * Sets a configuration option value and remembers the option's previous state,
     * so that {@see restoreConfigValues} can revert it correctly.
     */
    protected function setConfigValue(string $name, mixed $value, ?string $scope = 'global'): void
    {
        $configManager = self::getConfigManager($scope);
        $scopeKey = (string)$scope;
        if (
            !isset($this->configSnapshots[$scopeKey])
            || !\array_key_exists($name, $this->configSnapshots[$scopeKey])
        ) {
            $this->configSnapshots[$scopeKey][$name] = $configManager->get($name, false, true);
        }
        $configManager->set($name, $value);
        $configManager->flush();
    }

    /**
     * Restores all configuration option values changed via {@see setConfigValue}.
     *
     * An option that had no own value in the scope is reverted via reset() instead of set():
     * set() would create an own scope value that did not exist before, changing
     * the "use parent scope value" flag visible to all subsequent tests.
     */
    protected function restoreConfigValues(): void
    {
        foreach ($this->configSnapshots as $scopeKey => $settings) {
            $configManager = self::getConfigManager($scopeKey ?: null);
            foreach ($settings as $name => $previousValue) {
                $hadOwnValue = \is_array($previousValue)
                    && false === ($previousValue[ConfigManager::USE_PARENT_SCOPE_VALUE_KEY] ?? true);
                if ($hadOwnValue) {
                    $configManager->set($name, $previousValue[ConfigManager::VALUE_KEY]);
                } else {
                    $configManager->reset($name);
                }
            }
            $configManager->flush();
        }
        $this->configSnapshots = [];
    }
}
