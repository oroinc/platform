<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\Label;

/**
 * @property array $items
 */
trait DescriptionTrait
{
    /**
     * Indicates whether the description attribute exists.
     *
     * @return bool
     */
    public function hasDescription()
    {
        return array_key_exists(EntityDefinitionConfig::DESCRIPTION, $this->items);
    }

    /**
     * Gets the value of the description attribute.
     *
     * @return string|Label|null
     */
    public function getDescription()
    {
        return array_key_exists(EntityDefinitionConfig::DESCRIPTION, $this->items)
            ? $this->items[EntityDefinitionConfig::DESCRIPTION]
            : null;
    }

    /**
     * Sets the value of the description attribute.
     *
     * @param string|Label|null $description
     */
    public function setDescription($description)
    {
        if ($description) {
            $this->items[EntityDefinitionConfig::DESCRIPTION] = $description;
        } else {
            unset($this->items[EntityDefinitionConfig::DESCRIPTION]);
        }
    }
}
