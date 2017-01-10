<?php

namespace Oro\Bundle\ActivityBundle\Model;

/**
 * Provides an interface of an activity entity
 */
interface ActivityInterface
{
    /**
     * Checks if an entity of the given type can be associated with this activity entity
     *
     * @param string $targetClass The class name of the target entity
     *
     * @return bool
     */
    public function supportActivityTarget($targetClass);

    /**
     * Gets entities associated with this activity entity
     *
     * @param string|null $targetClass The class name of the target entity
     *
     * @return object[]
     */
    public function getActivityTargets($targetClass = null);

    /**
     * Checks is the given entity is associated with this activity entity
     *
     * @param object $target Any configurable entity that can be associated with this activity
     *
     * @return bool
     */
    public function hasActivityTarget($target);

    /**
     * Associates the given entity with this activity entity
     *
     * @param object $target Any configurable entity that can be associated with this activity
     *
     * @return self This object
     */
    public function addActivityTarget($target);

    /**
     * Removes the association of the given entity with this activity entity
     *
     * @param object $target Any configurable entity that can be associated with this activity
     *
     * @return self This object
     */
    public function removeActivityTarget($target);
}
