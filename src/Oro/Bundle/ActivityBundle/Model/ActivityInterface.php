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
     * Gets entities of the given type associated with this activity entity
     *
     * @param string $targetClass The class name of the target entity
     *
     * @return object[]
     */
    public function getActivityTargets($targetClass);

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

    /**
     * Gets full target entities list associated with activity
     * Please use this method carefully because of the performance reasons
     *
     * @return object[]
     */
    public function getActivityTargetEntities();
}
