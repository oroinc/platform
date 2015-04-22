<?php

namespace Oro\Component\PropertyAccess\Tests\Unit;

class PropertyAccessorArrayObjectTest extends PropertyAccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return new \ArrayObject($array);
    }
}
