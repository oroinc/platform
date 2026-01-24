<?php

namespace Oro\Bundle\FormBundle\Autocomplete;

/**
 * Defines the contract for converting items into their view representation.
 *
 * Implementations of this interface are responsible for transforming domain objects
 * or data structures into arrays suitable for display in autocomplete results or
 * other UI components.
 */
interface ConverterInterface
{
    /**
     * Converts item into an array that represents it in view.
     *
     * @param mixed $item
     * @return array
     */
    public function convertItem($item);
}
