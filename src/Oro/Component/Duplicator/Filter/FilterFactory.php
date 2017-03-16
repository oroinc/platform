<?php

namespace Oro\Component\Duplicator\Filter;

use DeepCopy\Filter\Filter as BaseFilter;

use Oro\Component\Duplicator\AbstractFactory;
use Oro\Component\Duplicator\ObjectType;

/**
 * @method BaseFilter create(ObjectType $objectType)
 */
class FilterFactory extends AbstractFactory
{
    /**
     * @return string
     */
    protected function getSupportedClassName()
    {
        return '\DeepCopy\Filter\Filter';
    }
}
