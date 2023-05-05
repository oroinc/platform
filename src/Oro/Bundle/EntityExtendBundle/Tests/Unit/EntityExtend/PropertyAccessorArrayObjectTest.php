<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EntityExtend;

class PropertyAccessorArrayObjectTest extends PropertyAccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return new \ArrayObject($array);
    }
}
