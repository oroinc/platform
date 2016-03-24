<?php

namespace Oro\Component\ChainProcessor;

/**
 * Interface to get an array representation of an object.
 */
interface ToArrayInterface
{
    /**
     * Gets an array representation of an object.
     *
     * @return array
     */
    public function toArray();
}
