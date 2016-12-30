<?php

namespace Oro\Bundle\ActivityListBundle\Model;

class ExtendActivityList
{
    /**
     * Constructor
     *
     * The real implementation of this method is auto generated.
     *
     * IMPORTANT: If the derived class has own constructor it must call parent constructor.
     */
    public function __construct()
    {
    }

    /**
     * Checks if an entity of the given type can be associated with this entity
     *
     * The real implementation of this method is auto generated.
     *
     * @param string $targetClass The class name of the target entity
     * @return bool
     */
    public function supportActivityListTarget($targetClass)
    {
        return false;
    }

    /**
     * Removes the association of the given entity and this entity
     *
     * The real implementation of this method is auto generated.
     *
     * @param object $target Any configurable entity that can be associated with this type of entity
     * @return object This object
     */
    public function removeActivityListTarget($target)
    {
    }

    /**
     * Checks is the given entity is associated with this entity
     *
     * The real implementation of this method is auto generated.
     *
     * @param object $target Any configurable entity that can be associated with this type of entity
     * @return bool
     */
    public function hasActivityListTarget($target)
    {
        return false;
    }

    /**
     * Gets entities associated with this entity
     *
     * The real implementation of this method is auto generated.
     *
     * @param string|null $targetClass The class name of the target entity
     * @return object[]
     */
    public function getActivityListTargets($targetClass = null)
    {
        return [];
    }

    /**
     * Associates the given entity with this entity
     *
     * The real implementation of this method is auto generated.
     *
     * @param object $target Any configurable entity that can be associated with this type of entity
     * @return object This object
     */
    public function addActivityListTarget($target)
    {
    }
}
