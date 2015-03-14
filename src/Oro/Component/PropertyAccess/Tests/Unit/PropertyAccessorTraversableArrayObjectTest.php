<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess;

use Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess\Fixtures\TraversableArrayObject;

class PropertyAccessorTraversableArrayObjectTest extends PropertyAccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return new TraversableArrayObject($array);
    }
}
