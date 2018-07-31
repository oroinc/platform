<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents the configuration of Data API resource.
 */
class Config implements \IteratorAggregate
{
    /** @var array */
    protected $items = [];

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        return ConfigUtil::convertItemsToArray($this->items);
    }

    /**
     * Indicates whether the configuration does not have any data.
     *
     * @return bool
     */
    public function isEmpty()
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
     *
     * @return bool
     */
    public function hasDefinition()
    {
        return $this->has(ConfigUtil::DEFINITION);
    }

    /**
     * Gets the configuration of an entity.
     *
     * @return EntityDefinitionConfig|null
     */
    public function getDefinition()
    {
        return $this->get(ConfigUtil::DEFINITION);
    }

    /**
     * Sets the configuration of an entity.
     *
     * @param EntityDefinitionConfig|null $definition
     */
    public function setDefinition(EntityDefinitionConfig $definition = null)
    {
        $this->set(ConfigUtil::DEFINITION, $definition);
    }

    /**
     * Indicates whether the configuration of filters exists.
     *
     * @return bool
     */
    public function hasFilters()
    {
        return $this->has(ConfigUtil::FILTERS);
    }

    /**
     * Gets the configuration of filters.
     *
     * @return FiltersConfig|null
     */
    public function getFilters()
    {
        return $this->get(ConfigUtil::FILTERS);
    }

    /**
     * Sets the configuration of filters.
     *
     * @param FiltersConfig|null $filters
     */
    public function setFilters(FiltersConfig $filters = null)
    {
        $this->set(ConfigUtil::FILTERS, $filters);
    }

    /**
     * Indicates whether the configuration of sorters exists.
     *
     * @return bool
     */
    public function hasSorters()
    {
        return $this->has(ConfigUtil::SORTERS);
    }

    /**
     * Gets the configuration of sorters.
     *
     * @return SortersConfig|null
     */
    public function getSorters()
    {
        return $this->get(ConfigUtil::SORTERS);
    }

    /**
     * Sets the configuration of sorters.
     *
     * @param SortersConfig|null $sorters
     */
    public function setSorters(SortersConfig $sorters = null)
    {
        $this->set(ConfigUtil::SORTERS, $sorters);
    }

    /**
     * Indicates whether the configuration of actions.
     *
     * @return bool
     */
    public function hasActions()
    {
        return $this->has(ConfigUtil::ACTIONS);
    }

    /**
     * Gets the configuration of actions.
     *
     * @return ActionsConfig|null
     */
    public function getActions()
    {
        return $this->get(ConfigUtil::ACTIONS);
    }

    /**
     * Sets the configuration of actions.
     *
     * @param ActionsConfig|null $actions
     */
    public function setActions(ActionsConfig $actions = null)
    {
        $this->set(ConfigUtil::ACTIONS, $actions);
    }

    /**
     * Indicates whether the configuration of sub-resources.
     *
     * @return bool
     */
    public function hasSubresources()
    {
        return $this->has(ConfigUtil::SUBRESOURCES);
    }

    /**
     * Gets the configuration of sub-resources.
     *
     * @return SubresourcesConfig|null
     */
    public function getSubresources()
    {
        return $this->get(ConfigUtil::SUBRESOURCES);
    }

    /**
     * Sets the configuration of sub-resources.
     *
     * @param SubresourcesConfig|null $subresources
     */
    public function setSubresources(SubresourcesConfig $subresources = null)
    {
        $this->set(ConfigUtil::SUBRESOURCES, $subresources);
    }

    /**
     * Checks whether the configuration attribute exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * Gets the configuration value.
     *
     * @param string $key
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        if (!\array_key_exists($key, $this->items)) {
            return $defaultValue;
        }

        return $this->items[$key];
    }

    /**
     * Sets the configuration value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        if (null !== $value) {
            $this->items[$key] = $value;
        } else {
            unset($this->items[$key]);
        }
    }

    /**
     * Removes the configuration value.
     *
     * @param string $key
     */
    public function remove($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Gets names of all configuration attributes.
     *
     * @return string[]
     */
    public function keys()
    {
        return \array_keys($this->items);
    }
}
