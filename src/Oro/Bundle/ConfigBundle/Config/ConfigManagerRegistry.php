<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\ConfigBundle\Provider\SystemConfigScopeNameProvider;

/**
 * Provides the scoped configuration managers.
 */
class ConfigManagerRegistry
{
    /** @var array<string, ConfigManager> */
    private array $configManagers = [];

    public function __construct(
        private readonly SystemConfigScopeNameProvider $systemConfigScopeNameProvider
    ) {
    }

    public function addConfigManager(string $scope, ConfigManager $configManager): void
    {
        $this->configManagers[$scope] = $configManager;
    }

    /**
     * Returns the configuration manager of the given scope.
     *
     * @param string $scope The configuration scope, e.g. "organization"
     *
     * @throws \LogicException When there is no configuration manager for the scope
     */
    public function getConfigManager(string $scope): ConfigManager
    {
        if (!isset($this->configManagers[$scope])) {
            throw new \LogicException(sprintf('The configuration scope "%s" does not exist.', $scope));
        }

        return $this->configManagers[$scope];
    }

    /**
     * Returns the configuration manager of the scope for which the given entity is the scope identifier.
     *
     * @param object|null $entity The scope identifier, e.g. an organization, or null for the global scope
     *
     * @throws \LogicException When no scope supports the entity, when the entity is null and there are no scopes,
     *                         or when there is no configuration manager for the resolved scope
     */
    public function getConfigManagerByEntity(?object $entity): ConfigManager
    {
        return $this->getConfigManager($this->systemConfigScopeNameProvider->getScopeNameByEntity($entity));
    }

    /**
     * Returns the names of the configuration scopes that have a configuration manager, e.g. "organization",
     * in the order the managers were added.
     *
     * @return string[]
     */
    public function getScopes(): array
    {
        return array_keys($this->configManagers);
    }
}
