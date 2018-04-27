<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * Adds the enable/disable inclusion property related methods to a configuration class.
 *
 * @property array $items
 */
trait InclusionTrait
{
    /**
     * Indicates whether the "disable_inclusion" option is set explicitly.
     *
     * @return bool
     */
    public function hasDisableInclusion()
    {
        return array_key_exists(EntityDefinitionConfig::DISABLE_INCLUSION, $this->items);
    }

    /**
     * Indicates whether an inclusion of related entities is enabled.
     *
     * @return bool
     */
    public function isInclusionEnabled()
    {
        if (!array_key_exists(EntityDefinitionConfig::DISABLE_INCLUSION, $this->items)) {
            return true;
        }

        return !$this->items[EntityDefinitionConfig::DISABLE_INCLUSION];
    }

    /**
     * Enables an inclusion of related entities.
     */
    public function enableInclusion()
    {
        $this->items[EntityDefinitionConfig::DISABLE_INCLUSION] = false;
    }

    /**
     * Disables an inclusion of related entities.
     */
    public function disableInclusion()
    {
        $this->items[EntityDefinitionConfig::DISABLE_INCLUSION] = true;
    }
}
