<?php

namespace Oro\Bundle\ActivityBundle\Model;

trait ExtendActivity
{
    /**
     * Gets entities of the given type associated with this activity entity
     *
     * The real implementation of this method is auto generated.
     *
     * @param string $targetClass The class name of the target entity
     * @return object[]
     */
    public function getActivityTargets($targetClass)
    {
        return null;
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
