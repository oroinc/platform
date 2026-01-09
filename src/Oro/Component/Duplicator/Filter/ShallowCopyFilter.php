<?php

namespace Oro\Component\Duplicator\Filter;

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
        $reflectionProperty = new \ReflectionProperty($object, $property);
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($object);
        $reflectionProperty->setValue($object, clone $value);
    }
}
