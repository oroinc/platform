<?php

namespace Oro\Component\Duplicator\Filter;

use DeepCopy\Filter\Filter as BaseFilter;
use Oro\Component\Duplicator\AbstractFactory;
use Oro\Component\Duplicator\ObjectType;

/**
 * Duplicator Filter factory with interface restrict
 *
 * @method BaseFilter create(ObjectType $objectType, array $constructorArgs = [])
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
