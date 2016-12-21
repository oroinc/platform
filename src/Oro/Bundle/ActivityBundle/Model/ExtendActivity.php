<?php

namespace Oro\Bundle\ActivityBundle\Model;

trait ExtendActivity
{
    /**
     * Checks if an entity of the given type can be associated with this activity entity
     *
     * The real implementation of this method is auto generated.
     *
     * @param string $targetClass The class name of the target entity
     * @return bool
     */
    public function supportActivityTarget($targetClass)
    {
        return false;
    }

    /**
     * Gets entities associated with this activity entity
     *
     * The real implementation of this method is auto generated.
     *
     * @param string|null $targetClass The class name of the target entity
     * @return object[]
     */
    public function getActivityTargets($targetClass = null)
    {
        return null;
    }

    /**
     * Checks is the given entity is associated with this activity entity
     *
     * The real implementation of this method is auto generated.
     *
     * @param object $target Any configurable entity that can be associated with this activity
     *
     * @return bool
     */
    public function hasActivityTarget($target)
    {
        return false;
    }

    /**
     * Associates the given entity with this activity entity
     *
     * The real implementation of this method is auto generated.
     *
     * @param object $target Any configurable entity that can be associated with this activity
     * @return object This object
     */
    public function addActivityTarget($target)
    {
        return $this;
    }

    /**
     * Removes the association of the given entity with this activity entity
     *
     * The real implementation of this method is auto generated.
     *
     * @param object $target Any configurable entity that can be associated with this activity
     * @return object This object
     */
    public function removeActivityTarget($target)
    {
        return $this;
    }
}
