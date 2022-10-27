<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Filter;

use DeepCopy\Reflection\ReflectionHelper;
use Oro\Component\Duplicator\Filter\Filter;

/**
 * Changes DateTime parameters if source is draft.
 */
class DateTimeFilter implements Filter
{
    /**
     * @inheritDoc
     */
    public function apply($object, $property, $objectCopier): void
    {
        $reflectionProperty = ReflectionHelper::getProperty($object, $property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, new \DateTime('now', new \DateTimeZone('UTC')));
    }
}
