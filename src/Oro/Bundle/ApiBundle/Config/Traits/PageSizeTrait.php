<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * @property array $items
 */
trait PageSizeTrait
{
    /**
     * Indicates whether the default page size is set.
     *
     * @return bool
     */
    public function hasPageSize()
    {
        return array_key_exists(EntityDefinitionConfig::PAGE_SIZE, $this->items);
    }

    /**
     * Gets the default page size.
     *
     * @return int|null A positive number
     *                  NULL if the default page size should be set be a processor
     *                  -1 if the pagination should be disabled
     */
    public function getPageSize()
    {
        return array_key_exists(EntityDefinitionConfig::PAGE_SIZE, $this->items)
            ? $this->items[EntityDefinitionConfig::PAGE_SIZE]
            : null;
    }

    /**
     * Sets the default page size.
     * Set NULL if the default page size should be set be a processor.
     * Set -1 if the pagination should be disabled.
     * Set a positive number to set own page size that should be used as a default one.
     *
     * @param int|null $pageSize A positive number, NULL or -1
     */
    public function setPageSize($pageSize = null)
    {
        if (null === $pageSize) {
            unset($this->items[EntityDefinitionConfig::PAGE_SIZE]);
        } else {
            $pageSize = (int)$pageSize;

            $this->items[EntityDefinitionConfig::PAGE_SIZE] = $pageSize >= 0 ? $pageSize : -1;
        }
    }
}
