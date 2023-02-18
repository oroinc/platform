<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\FormContextTrait;

/**
 * The base execution context for processors for "update_relationship", "add_relationship"
 * and "delete_relationship" actions.
 */
class ChangeRelationshipContext extends SubresourceContext implements FormContext
{
    use FormContextTrait;

    private ?object $parentEntity = null;

    /**
     * Checks whether the parent entity exists.
     */
    public function hasParentEntity(): bool
    {
        return null !== $this->parentEntity;
    }

    /**
     * Gets the parent entity object.
     */
    public function getParentEntity(): ?object
    {
        return $this->parentEntity;
    }

    /**
     * Sets the parent entity object.
     */
    public function setParentEntity(?object $parentEntity): void
    {
        $this->parentEntity = $parentEntity;
    }
}
