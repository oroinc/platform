<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\Label;

/**
 * @property array $items
 */
trait PluralLabelTrait
{
    /**
     * Indicates whether the plural label attribute exists.
     *
     * @return bool
     */
    public function hasPluralLabel()
    {
        return array_key_exists(EntityDefinitionConfig::PLURAL_LABEL, $this->items);
    }

    /**
     * Gets the value of the plural label attribute.
     *
     * @return string|Label|null
     */
    public function getPluralLabel()
    {
        return array_key_exists(EntityDefinitionConfig::PLURAL_LABEL, $this->items)
            ? $this->items[EntityDefinitionConfig::PLURAL_LABEL]
            : null;
    }

    /**
     * Sets the value of the plural label attribute.
     *
     * @param string|Label|null $pluralLabel
     */
    public function setPluralLabel($pluralLabel)
    {
        if ($pluralLabel) {
            $this->items[EntityDefinitionConfig::PLURAL_LABEL] = $pluralLabel;
        } else {
            unset($this->items[EntityDefinitionConfig::PLURAL_LABEL]);
        }
    }
}
