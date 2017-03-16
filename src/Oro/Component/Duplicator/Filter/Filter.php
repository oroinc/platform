<?php

namespace Oro\Component\Duplicator\Filter;

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
