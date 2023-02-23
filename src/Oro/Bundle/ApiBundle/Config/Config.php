<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents the configuration of API resource.
 */
class Config implements \IteratorAggregate
{
    private array $items = [];

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Gets a native PHP array representation of the configuration.
     */
    public function toArray(): array
    {
        return ConfigUtil::convertItemsToArray($this->items);
    }

    /**
     * Indicates whether the configuration does not have any data.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->items = ConfigUtil::cloneItems($this->items);
    }

    /**
     * Indicates whether the configuration of an entity exists.
     */
    public function hasDefinition(): bool
    {
        return $this->has(ConfigUtil::DEFINITION);
    }

    /**
     * Gets the configuration of an entity.
     */
    public function getDefinition(): ?EntityDefinitionConfig
    {
        return $this->get(ConfigUtil::DEFINITION);
    }

    /**
     * Sets the configuration of an entity.
     */
    public function setDefinition(?EntityDefinitionConfig $definition): void
    {
        $this->set(ConfigUtil::DEFINITION, $definition);
    }

    /**
     * Indicates whether the configuration of filters exists.
     */
    public function hasFilters(): bool
    {
        return $this->has(ConfigUtil::FILTERS);
    }

    /**
     * Gets the configuration of filters.
     */
    public function getFilters(): ?FiltersConfig
    {
        return $this->get(ConfigUtil::FILTERS);
    }

    /**
     * Sets the configuration of filters.
     */
    public function setFilters(?FiltersConfig $filters): void
    {
        $this->set(ConfigUtil::FILTERS, $filters);
    }

    /**
     * Indicates whether the configuration of sorters exists.
     */
    public function hasSorters(): bool
    {
        return $this->has(ConfigUtil::SORTERS);
    }

    /**
     * Gets the configuration of sorters.
     */
    public function getSorters(): ?SortersConfig
    {
        return $this->get(ConfigUtil::SORTERS);
    }

    /**
     * Sets the configuration of sorters.
     */
    public function setSorters(?SortersConfig $sorters): void
    {
        $this->set(ConfigUtil::SORTERS, $sorters);
    }

    /**
     * Indicates whether the configuration of actions.
     */
    public function hasActions(): bool
    {
        return $this->has(ConfigUtil::ACTIONS);
    }

    /**
     * Gets the configuration of actions.
     */
    public function getActions(): ?ActionsConfig
    {
        return $this->get(ConfigUtil::ACTIONS);
    }

    /**
     * Sets the configuration of actions.
     */
    public function setActions(?ActionsConfig $actions): void
    {
        $this->set(ConfigUtil::ACTIONS, $actions);
    }

    /**
     * Indicates whether the configuration of sub-resources.
     */
    public function hasSubresources(): bool
    {
        return $this->has(ConfigUtil::SUBRESOURCES);
    }

    /**
     * Gets the configuration of sub-resources.
     */
    public function getSubresources(): ?SubresourcesConfig
    {
        return $this->get(ConfigUtil::SUBRESOURCES);
    }

    /**
     * Sets the configuration of sub-resources.
     */
    public function setSubresources(?SubresourcesConfig $subresources): void
    {
        $this->set(ConfigUtil::SUBRESOURCES, $subresources);
    }

    /**
     * Indicates whether the configuration attribute exists.
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * Gets the configuration value.
     */
    public function get(string $key, mixed $defaultValue = null): mixed
    {
        if (!\array_key_exists($key, $this->items)) {
            return $defaultValue;
        }

        return $this->items[$key];
    }

    /**
     * Sets the configuration value.
     */
    public function set(string $key, mixed $value): void
    {
        if (null !== $value) {
            $this->items[$key] = $value;
        } else {
            unset($this->items[$key]);
        }
    }

    /**
     * Removes the configuration value.
     */
    public function remove(string $key): void
    {
        unset($this->items[$key]);
    }

    /**
     * Gets names of all configuration attributes.
     *
     * @return string[]
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }
}
