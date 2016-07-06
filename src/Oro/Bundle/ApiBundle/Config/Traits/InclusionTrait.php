<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * @property array $items
 */
trait InclusionTrait
{
    /**
     * Indicates whether whether an inclusion of related entities is enabled.
     *
     * @return bool
     */
    public function isInclusionEnabled()
    {
        return array_key_exists(EntityDefinitionConfig::DISABLE_INCLUSION, $this->items)
            ? !$this->items[EntityDefinitionConfig::DISABLE_INCLUSION]
            : true;
    }

    /**
     * Enables an inclusion of related entities.
     */
    public function enableInclusion()
    {
        unset($this->items[EntityDefinitionConfig::DISABLE_INCLUSION]);
    }

    /**
     * Disables an inclusion of related entities.
     */
    public function disableInclusion()
    {
        $this->items[EntityDefinitionConfig::DISABLE_INCLUSION] = true;
    }
}
