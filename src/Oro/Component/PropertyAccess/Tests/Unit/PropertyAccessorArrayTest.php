<?php

namespace Oro\Component\PropertyAccess\Tests\Unit;

class PropertyAccessorArrayTest extends PropertyAccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return $array;
    }
}
