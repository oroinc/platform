<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;

/**
 * @property array $items
 */
trait StatusCodesTrait
{
    /**
     * Gets response status codes.
     *
     * @return StatusCodesConfig|null
     */
    public function getStatusCodes()
    {
        return array_key_exists(EntityDefinitionConfig::STATUS_CODES, $this->items)
            ? $this->items[EntityDefinitionConfig::STATUS_CODES]
            : null;
    }

    /**
     * Sets response status codes.
     *
     * @param StatusCodesConfig|null $statusCodes
     */
    public function setStatusCodes(StatusCodesConfig $statusCodes = null)
    {
        if (null !== $statusCodes) {
            $this->items[EntityDefinitionConfig::STATUS_CODES] = $statusCodes;
        } else {
            unset($this->items[EntityDefinitionConfig::STATUS_CODES]);
        }
    }
}
