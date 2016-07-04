<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * @property array $items
 */
trait FieldsetTrait
{
    /**
     * Indicates whether indicates whether a requesting of a restricted set of fields is enabled.
     *
     * @return bool
     */
    public function isFieldsetEnabled()
    {
        return array_key_exists(EntityDefinitionConfig::DISABLE_FIELDSET, $this->items)
            ? !$this->items[EntityDefinitionConfig::DISABLE_FIELDSET]
            : true;
    }

    /**
     * Enables a requesting of a restricted set of fields.
     */
    public function enableFieldset()
    {
        unset($this->items[EntityDefinitionConfig::DISABLE_FIELDSET]);
    }

    /**
     * Disables a requesting of a restricted set of fields.
     */
    public function disableFieldset()
    {
        $this->items[EntityDefinitionConfig::DISABLE_FIELDSET] = true;
    }
}
