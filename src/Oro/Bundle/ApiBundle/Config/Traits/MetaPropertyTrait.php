<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * @property array $items
 */
trait MetaPropertyTrait
{
    /**
     * Indicates whether a requesting of additional meta properties is enabled.
     *
     * @return bool
     */
    public function isMetaPropertiesEnabled()
    {
        if (!array_key_exists(EntityDefinitionConfig::DISABLE_META_PROPERTIES, $this->items)) {
            return true;
        }

        return !$this->items[EntityDefinitionConfig::DISABLE_META_PROPERTIES];
    }

    /**
     * Enables a requesting of additional meta properties.
     */
    public function enableMetaProperties()
    {
        unset($this->items[EntityDefinitionConfig::DISABLE_META_PROPERTIES]);
    }

    /**
     * Disables a requesting of additional meta properties.
     */
    public function disableMetaProperties()
    {
        $this->items[EntityDefinitionConfig::DISABLE_META_PROPERTIES] = true;
    }
}
