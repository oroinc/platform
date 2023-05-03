<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EntityExtend;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\PropertyAccess\TraversableArrayObject;

class PropertyAccessorTraversableArrayObjectTest extends PropertyAccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return new TraversableArrayObject($array);
    }
}
