<?php

namespace Oro\Component\PropertyAccess\Tests\Unit;

use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\NonTraversableArrayObject;

class PropertyAccessorNonTraversableArrayObjectTest extends PropertyAccessorArrayAccessTest
{
    protected function getContainer(array $array)
    {
        return new NonTraversableArrayObject($array);
    }
}
