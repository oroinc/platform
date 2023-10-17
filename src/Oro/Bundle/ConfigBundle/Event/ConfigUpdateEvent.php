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

    public function __construct(array $changeSet, string $scope, int $scopeId)
    {
        $this->changeSet = new ConfigChangeSet($changeSet);
        $this->scope = $scope;
        $this->scopeId = $scopeId;
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

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getScopeId(): int
    {
        return $this->scopeId;
    }
}
