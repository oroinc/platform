<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Traits;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

trait ConfigManagerAwareTestTrait
{
    /**
     * The previous states of the options changed via {@see setConfigValue}, grouped by the scope,
     * with one entry per scope identifier exactly as it was given (NULL when no identifier was given).
     *
     * @var array<string, list<array{scopeIdentifier: object|int|null, values: array<string, mixed>}>>
     */
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
     *
     * @param string          $name            The configuration option name
     * @param mixed           $value           The new value of the configuration option
     * @param string|null     $scope           The configuration scope (e.g.: global, organization, user, etc.)
     *                                         or NULL to use the configuration manager for the current scope
     * @param object|int|null $scopeIdentifier The scope entity or its ID (e.g.: an organization for
     *                                         the organization scope) or NULL for the current scope entity
     */
    protected function setConfigValue(
        string $name,
        mixed $value,
        ?string $scope = 'global',
        object|int|null $scopeIdentifier = null
    ): void {
        $configManager = self::getConfigManager($scope);
        $scopeKey = (string)$scope;
        $snapshotIndex = null;
        foreach ($this->configSnapshots[$scopeKey] ?? [] as $index => $snapshot) {
            if ($snapshot['scopeIdentifier'] === $scopeIdentifier) {
                $snapshotIndex = $index;
                break;
            }
        }
        if (null === $snapshotIndex) {
            $snapshotIndex = \count($this->configSnapshots[$scopeKey] ?? []);
            $this->configSnapshots[$scopeKey][$snapshotIndex] = [
                'scopeIdentifier' => $scopeIdentifier,
                'values' => []
            ];
        }
        if (!\array_key_exists($name, $this->configSnapshots[$scopeKey][$snapshotIndex]['values'])) {
            $this->configSnapshots[$scopeKey][$snapshotIndex]['values'][$name] =
                $configManager->get($name, false, true, $scopeIdentifier);
        }
        $configManager->set($name, $value, $scopeIdentifier);
        $configManager->flush($scopeIdentifier);
    }

    /**
     * Restores all configuration option values changed via {@see setConfigValue},
     * each one in the same scope and for the same scope identifier it was changed for.
     *
     * An option that had no own value in the scope is reverted via reset() instead of set():
     * set() would create an own scope value that did not exist before, changing
     * the "use parent scope value" flag visible to all subsequent tests.
     */
    protected function restoreConfigValues(): void
    {
        foreach ($this->configSnapshots as $scopeKey => $scopeSnapshots) {
            $configManager = self::getConfigManager($scopeKey ?: null);
            foreach ($scopeSnapshots as $snapshot) {
                $scopeIdentifier = $snapshot['scopeIdentifier'];
                foreach ($snapshot['values'] as $name => $previousValue) {
                    $hadOwnValue = \is_array($previousValue)
                        && false === ($previousValue[ConfigManager::USE_PARENT_SCOPE_VALUE_KEY] ?? true);
                    if ($hadOwnValue) {
                        $configManager->set($name, $previousValue[ConfigManager::VALUE_KEY], $scopeIdentifier);
                    } else {
                        $configManager->reset($name, $scopeIdentifier);
                    }
                }
                $configManager->flush($scopeIdentifier);
            }
        }
        $this->configSnapshots = [];
    }
}
