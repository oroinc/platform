<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * Adds the enable/disable fieldset property related methods to a configuration class.
 *
 * @property array $items
 */
trait FieldsetTrait
{
    /**
     * Indicates whether the "disable_fieldset" option is set explicitly.
     *
     * @return bool
     */
    public function hasDisableFieldset()
    {
        return array_key_exists(EntityDefinitionConfig::DISABLE_FIELDSET, $this->items);
    }

    /**
     * Indicates whether indicates whether a requesting of a restricted set of fields is enabled.
     *
     * @return bool
     */
    public function isFieldsetEnabled()
    {
        if (!array_key_exists(EntityDefinitionConfig::DISABLE_FIELDSET, $this->items)) {
            return true;
        }

        return !$this->items[EntityDefinitionConfig::DISABLE_FIELDSET];
    }

    /**
     * Enables a requesting of a restricted set of fields.
     */
    public function enableFieldset()
    {
        $this->items[EntityDefinitionConfig::DISABLE_FIELDSET] = false;
    }

    /**
     * Disables a requesting of a restricted set of fields.
     */
    public function disableFieldset()
    {
        $this->items[EntityDefinitionConfig::DISABLE_FIELDSET] = true;
    }
}
