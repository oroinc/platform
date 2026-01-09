<?php

namespace Oro\Component\Layout\Loader\Generator;

/**
 * Defines the contract for layout updates that depend on a specific layout element.
 *
 * Implementations of this interface declare a dependency on a specific layout item,
 * indicating that the update should only be applied when that item exists in the layout.
 */
interface ElementDependentLayoutUpdateInterface
{
    /**
     * @return string The id of an layout item to which this layout update depends
     */
    public function getElement();
}
