<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Filter;

use DeepCopy\Reflection\ReflectionHelper;
use Oro\Component\Duplicator\Filter\ReplaceValueFilter;

/**
 * Changes parameter "source" if it does not exist in draft.
 */
class SourceFilter extends ReplaceValueFilter
{
    #[\Override]
    public function apply($object, $property, $objectCopier): void
    {
        $reflectionProperty = ReflectionHelper::getProperty($object, $property);

        parent::apply($object, $property, $objectCopier);
    }
}
