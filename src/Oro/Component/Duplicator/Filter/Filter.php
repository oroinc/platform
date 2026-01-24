<?php

namespace Oro\Component\Duplicator\Filter;

/**
 * Defines the contract for object duplication filters.
 *
 * Filters are applied during the deep copy process to customize how specific
 * object properties are handled during duplication.
 */
interface Filter extends \DeepCopy\Filter\Filter
{
    /**
     * Apply the filter to the object.
     * @param object   $object
     * @param string   $property
     * @param callable $objectCopier
     */
    public function apply($object, $property, $objectCopier);
}
