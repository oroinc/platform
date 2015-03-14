<?php

namespace Oro\Component\PropertyAccess\Tests\Unit;

use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\TraversableArrayObject;

class PropertyAccessorTraversableArrayObjectTest extends PropertyAccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return new TraversableArrayObject($array);
    }
}
