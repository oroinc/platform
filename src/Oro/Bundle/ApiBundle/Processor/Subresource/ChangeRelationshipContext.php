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

    /** @var object|null */
    private $parentEntity;

    /**
     * Checks whether the parent entity exists.
     *
     * @return bool
     */
    public function hasParentEntity()
    {
        return null !== $this->parentEntity;
    }

    /**
     * Gets the parent entity object.
     *
     * @return object|null
     */
    public function getParentEntity()
    {
        return $this->parentEntity;
    }

    /**
     * Sets the parent entity object.
     *
     * @param object|null $parentEntity
     */
    public function setParentEntity($parentEntity)
    {
        $this->parentEntity = $parentEntity;
    }
}
