<?php

namespace Oro\Component\Duplicator\Filter;

class ShallowCopyFilter implements Filter
{
    /**
     * {@inheritdoc}
     */
    public function apply($object, $property, $objectCopier)
    {
        $reflectionProperty = new \ReflectionProperty($object, $property);
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($object);
        $reflectionProperty->setValue($object, clone $value);
    }
}
