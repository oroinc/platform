<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class Config implements \IteratorAggregate
{
    use Traits\ConfigTrait;

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
        $result = [];
        foreach ($this->items as $sectionName => $config) {
            $result[$sectionName] = is_object($config) && method_exists($config, 'toArray')
                ? $config->toArray()
                : $config;
        }

        return $result;
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
     * Make a deep copy of object.
     */
    public function __clone()
    {
        $this->items = array_map(
            function ($value) {
                return is_object($value) ? clone $value : $value;
            },
            $this->items
        );
    }

    /**
     * Checks whether the configuration of an entity exists.
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
     * Checks whether the configuration of filters exists.
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
     * Checks whether the configuration of sorters exists.
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
}
