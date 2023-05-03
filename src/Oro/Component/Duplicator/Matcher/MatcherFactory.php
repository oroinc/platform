<?php

namespace Oro\Component\Duplicator\Matcher;

use DeepCopy\Matcher\Matcher as BaseMatcher;
use Oro\Component\Duplicator\AbstractFactory;
use Oro\Component\Duplicator\ObjectType;

/**
 * Duplicator Matcher factory with interface restrict
 *
 * @method BaseMatcher create(ObjectType $objectType, array $constructorArgs = [])
 */
class MatcherFactory extends AbstractFactory
{
    /**
     * @return string
     */
    protected function getSupportedClassName()
    {
        return '\DeepCopy\Matcher\Matcher';
    }
}
