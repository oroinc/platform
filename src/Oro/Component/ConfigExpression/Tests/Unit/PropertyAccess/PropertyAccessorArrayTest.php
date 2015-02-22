<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess;

class PropertyAccessorArrayTest extends PropertyAccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return $array;
    }
}
