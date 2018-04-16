<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * Adds the enable/disable sorting property related methods to a configuration class.
 *
 * @property array $items
 */
trait SortingTrait
{
    /**
     * Indicates whether a sorting is enabled.
     *
     * @return bool
     */
    public function isSortingEnabled()
    {
        if (!array_key_exists(EntityDefinitionConfig::DISABLE_SORTING, $this->items)) {
            return true;
        }

        return !$this->items[EntityDefinitionConfig::DISABLE_SORTING];
    }

    /**
     * Enables a sorting.
     */
    public function enableSorting()
    {
        unset($this->items[EntityDefinitionConfig::DISABLE_SORTING]);
    }

    /**
     * Disables a sorting.
     */
    public function disableSorting()
    {
        $this->items[EntityDefinitionConfig::DISABLE_SORTING] = true;
    }
}
