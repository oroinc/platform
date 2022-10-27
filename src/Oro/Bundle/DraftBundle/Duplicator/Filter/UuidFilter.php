<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Filter;

use DeepCopy\Reflection\ReflectionHelper;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\Duplicator\Filter\Filter;

/**
 * Changes parameter "uuid" if it does not exist in draft.
 */
class UuidFilter implements Filter
{
    /**
     * {@inheritdoc}
     */
    public function apply($object, $property, $objectCopier): void
    {
        $reflectionProperty = ReflectionHelper::getProperty($object, $property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, UUIDGenerator::v4());
    }
}
