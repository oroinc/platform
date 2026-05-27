<?php

namespace Oro\Component\Duplicator\Filter;

use DeepCopy\Reflection\ReflectionHelper;

/**
 * Performs a shallow copy of a property value during object duplication.
 *
 * This filter creates a shallow clone of a property's value, useful for
 * duplicating objects that contain references to other objects that should
 * be cloned but not deeply copied.
 */
class ShallowCopyFilter implements Filter
{
    #[\Override]
    public function apply($object, $property, $objectCopier)
    {
        $reflectionProperty = ReflectionHelper::getProperty($object, $property);
        $value = $reflectionProperty->getValue($object);
        $reflectionProperty->setValue($object, clone $value);
    }
}
