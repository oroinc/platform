<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
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
        return array_key_exists(EntityDefinitionConfig::DISABLE_SORTING, $this->items)
            ? !$this->items[EntityDefinitionConfig::DISABLE_SORTING]
            : true;
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
