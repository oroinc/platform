<?php

namespace Oro\Bundle\ConfigBundle\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The event that is fired after system configuration form data are saved.
 */
class ConfigUpdateEvent extends Event
{
    public const EVENT_NAME = 'oro_config.update_after';

    private ConfigChangeSet $changeSet;
    private string $scope;
    private int $scopeId;
    private array $useParentScopeChanges;

    public function __construct(
        array $changeSet,
        string $scope,
        int $scopeId,
        array $useParentScopeChanges = []
    ) {
        $this->changeSet = new ConfigChangeSet($changeSet);
        $this->scope = $scope;
        $this->scopeId = $scopeId;
        $this->useParentScopeChanges = $useParentScopeChanges;
    }

    /**
     * Gets changed configuration values.
     *
     * @return array [name => ['new' => value, 'old' => value], ...]
     */
    public function getChangeSet(): array
    {
        return $this->changeSet->getChanges();
    }

    /**
     * Checks whenever configuration value is changed.
     */
    public function isChanged(string $name): bool
    {
        return $this->changeSet->isChanged($name);
    }

    /**
     * Gets a new value for the given configuration option.
     *
     * @throws \LogicException when the given configuration option was not changed
     */
    public function getNewValue(string $name): mixed
    {
        return $this->changeSet->getNewValue($name);
    }

    /**
     * Gets an old value for the given configuration option.
     *
     * @throws \LogicException when the given configuration option was not changed
     */
    public function getOldValue(string $name): mixed
    {
        return $this->changeSet->getOldValue($name);
    }

    /**
     * Gets settings that started or stopped using the value of the parent scope — the "Use default" /
     * "Use Organization" / ... checkbox of the configuration form — while their value stayed the same.
     * A setting whose value did change is reported by the change set instead.
     *
     * @return array [name => ['new' => value, 'old' => value, 'action' => action], ...]
     */
    public function getUseParentScopeChanges(): array
    {
        return $this->useParentScopeChanges;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getScopeId(): int
    {
        return $this->scopeId;
    }
}
