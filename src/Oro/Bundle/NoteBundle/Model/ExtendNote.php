<?php

namespace Oro\Bundle\NoteBundle\Model;

class ExtendNote
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
     * Checks if this note can be associated with the given target entity type
     *
     * The real implementation of this method is auto generated.
     *
     * @param string $targetClass The class name of the target entity
     * @return bool
     */
    public function supportTarget($targetClass)
    {
        return false;
    }

    /**
     * Gets the entity this note is associated with
     *
     * The real implementation of this method is auto generated.
     *
     * @return object|null Any configurable entity
     */
    public function getTarget()
    {
        return null;
    }

    /**
     * Sets the entity this note is associated with
     *
     * The real implementation of this method is auto generated.
     *
     * @param object $target Any configurable entity that can have notes
     *
     * @return object This object
     */
    public function setTarget($target)
    {
        return $this;
    }
}
