<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\Label;

/**
 * @property array $items
 */
trait LabelTrait
{
    /**
     * Indicates whether the label attribute exists.
     *
     * @return bool
     */
    public function hasLabel()
    {
        return array_key_exists(EntityDefinitionConfig::LABEL, $this->items);
    }

    /**
     * Gets the value of the label attribute.
     *
     * @return string|Label|null
     */
    public function getLabel()
    {
        return array_key_exists(EntityDefinitionConfig::LABEL, $this->items)
            ? $this->items[EntityDefinitionConfig::LABEL]
            : null;
    }

    /**
     * Sets the value of the label attribute.
     *
     * @param string|Label|null $label
     */
    public function setLabel($label)
    {
        if ($label) {
            $this->items[EntityDefinitionConfig::LABEL] = $label;
        } else {
            unset($this->items[EntityDefinitionConfig::LABEL]);
        }
    }
}
