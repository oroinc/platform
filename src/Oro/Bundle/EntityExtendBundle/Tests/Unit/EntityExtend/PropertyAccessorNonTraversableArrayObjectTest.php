<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EntityExtend;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\PropertyAccess\NonTraversableArrayObject;

class PropertyAccessorNonTraversableArrayObjectTest extends PropertyAccessorArrayAccessTest
{
    #[\Override]
    protected function getContainer(array $array)
    {
        return new NonTraversableArrayObject($array);
    }
}
