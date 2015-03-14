<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess;

class PropertyAccessorArrayObjectTest extends PropertyAccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return new \ArrayObject($array);
    }
}
