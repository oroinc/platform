<?php

namespace Oro\Bundle\ConfigBundle\Config;

/**
 * Represents a storage for changed configuration options.
 */
class ConfigChangeSet
{
    private array $changeSet;

    public function __construct(array $changeSet)
    {
        $this->changeSet = $changeSet;
    }

    /**
     * Gets config change set.
     *
     * @return array [name => ['new' => value, 'old' => value], ...]
     */
    public function getChanges(): array
    {
        return $this->changeSet;
    }

    /**
     * Checks whenever configuration value is changed.
     */
    public function isChanged(string $name): bool
    {
        return !empty($this->changeSet[$name]);
    }

    /**
     * Gets a new value for the given configuration option.
     *
     * @throws \LogicException when the given configuration option was not changed
     */
    public function getNewValue(string $name): mixed
    {
        $this->assertChanged($name);

        return $this->changeSet[$name]['new'];
    }

    /**
     * Gets an old value for the given configuration option.
     *
     * @throws \LogicException when the given configuration option was not changed
     */
    public function getOldValue(string $name): mixed
    {
        $this->assertChanged($name);

        return $this->changeSet[$name]['old'];
    }

    private function assertChanged(string $name): void
    {
        if (!$this->isChanged($name)) {
            throw new \LogicException(sprintf('Could not retrieve a value for "%s".', $name));
        }
    }
}
