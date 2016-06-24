<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * @property array $items
 */
trait MaxResultsTrait
{
    /**
     * Indicates whether the maximum number of items is set.
     *
     * @return bool
     */
    public function hasMaxResults()
    {
        return array_key_exists(EntityDefinitionConfig::MAX_RESULTS, $this->items);
    }

    /**
     * Gets the maximum number of items in the result.
     *
     * @return int|null The requested maximum number of items, NULL or -1 if not limited
     */
    public function getMaxResults()
    {
        return array_key_exists(EntityDefinitionConfig::MAX_RESULTS, $this->items)
            ? $this->items[EntityDefinitionConfig::MAX_RESULTS]
            : null;
    }

    /**
     * Sets the maximum number of items in the result.
     * Set NULL to use a default limit.
     * Set -1 (it means unlimited), zero or positive number to set own limit.
     *
     * @param int|null $maxResults The maximum number of items, NULL or -1 to set unlimited
     */
    public function setMaxResults($maxResults = null)
    {
        if (null === $maxResults) {
            unset($this->items[EntityDefinitionConfig::MAX_RESULTS]);
        } else {
            $maxResults = (int)$maxResults;

            $this->items[EntityDefinitionConfig::MAX_RESULTS] = $maxResults >= 0 ? $maxResults : -1;
        }
    }
}
