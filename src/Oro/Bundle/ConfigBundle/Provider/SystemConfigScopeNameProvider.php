<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\AbstractScopeManager;

/**
 * Provides the name of the system configuration scope for which an entity is the scope identifier,
 * e.g. "organization" for an organization, or the name of the global scope for no entity.
 */
class SystemConfigScopeNameProvider
{
    /** @var array<string, AbstractScopeManager> */
    private array $scopeManagers = [];

    /**
     * Adds the scope manager of the given scope. The scope managers must be added in the order of the scope priority,
     * the highest priority first.
     */
    public function addScopeManager(string $scope, AbstractScopeManager $scopeManager): void
    {
        $this->scopeManagers[$scope] = $scopeManager;
    }

    /**
     * Returns the name of the highest-priority system configuration scope for which the given entity
     * is the scope identifier, e.g. "organization". For null, returns the name of the lowest-priority scope,
     * i.e. the global one, which is the last added scope.
     *
     * @param object|null $entity The scope identifier, e.g. an organization, or null for the global scope
     *
     * @throws \LogicException When no scope supports the entity, or when the entity is null and no scopes are added
     */
    public function getScopeNameByEntity(?object $entity): string
    {
        if (null === $entity) {
            if (!$this->scopeManagers) {
                throw new \LogicException('There is no global system configuration scope.');
            }

            return array_key_last($this->scopeManagers);
        }

        foreach ($this->scopeManagers as $scope => $scopeManager) {
            // The global scope manager always gives 0, the other scope managers give 0 for an unsaved entity.
            if ($scopeManager->getScopeIdFromEntity($entity)) {
                return $scope;
            }
        }

        throw new \LogicException(sprintf(
            'There is no system configuration scope for the entity "%s".',
            get_debug_type($entity)
        ));
    }
}
