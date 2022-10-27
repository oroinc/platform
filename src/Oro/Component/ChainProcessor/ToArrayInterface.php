<?php

namespace Oro\Component\ChainProcessor;

/**
 * Interface to get an array representation of an object.
 */
interface ToArrayInterface
{
    /**
     * Gets a native PHP array representation of the object.
     *
     * @return array [key => value, ...]
     */
    public function toArray(): array;
}
