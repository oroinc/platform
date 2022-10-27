<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * The metadata for an entity property that provides some meta information about this entity.
 */
class MetaPropertyMetadata extends PropertyMetadata
{
    private ?string $resultName = null;

    /**
     * Gets the name by which the meta property should be returned in the response.
     */
    public function getResultName(): ?string
    {
        return $this->resultName ?? $this->getName();
    }

    /**
     * Sets the name by which the meta property should be returned in the response.
     */
    public function setResultName(?string $resultName): void
    {
        $this->resultName = $resultName;
    }
}
