<?php

namespace Oro\Component\Layout;

/**
 * All objects you want to add to the layout context must implement this interface.
 */
interface ContextItemInterface
{
    /**
     * Returns a string representation of the object.
     * This string is used as a part of the key for the layout profiler.
     *
     * @return string
     */
    public function toString();

    /**
     * Return a hash of the object.
     * This string is used as a part of the key for the layout cache.
     *
     * @return string
     */
    public function getHash();
}
