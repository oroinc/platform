<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess;

use Oro\Component\ConfigExpression\Tests\Unit\PropertyAccess\Fixtures\NonTraversableArrayObject;

class PropertyAccessorNonTraversableArrayObjectTest extends PropertyAccessorArrayAccessTest
{
    protected function getContainer(array $array)
    {
        return new NonTraversableArrayObject($array);
    }
}
