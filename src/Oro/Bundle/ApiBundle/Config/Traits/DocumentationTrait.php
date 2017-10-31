<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * @property array $items
 */
trait DocumentationTrait
{
    /**
     * Indicates whether the documentation attribute exists.
     *
     * @return bool
     */
    public function hasDocumentation()
    {
        return array_key_exists(EntityDefinitionConfig::DOCUMENTATION, $this->items);
    }

    /**
     * Gets a detailed documentation of API resource.
     *
     * @return string|null
     */
    public function getDocumentation()
    {
        if (!array_key_exists(EntityDefinitionConfig::DOCUMENTATION, $this->items)) {
            return null;
        }

        return $this->items[EntityDefinitionConfig::DOCUMENTATION];
    }

    /**
     * Sets a detailed documentation of API resource.
     *
     * @param string|null $documentation
     */
    public function setDocumentation($documentation)
    {
        if ($documentation) {
            $this->items[EntityDefinitionConfig::DOCUMENTATION] = $documentation;
        } else {
            unset($this->items[EntityDefinitionConfig::DOCUMENTATION]);
        }
    }
}
