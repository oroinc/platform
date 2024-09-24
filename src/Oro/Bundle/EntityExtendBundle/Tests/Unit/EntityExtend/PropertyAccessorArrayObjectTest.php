<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EntityExtend;

class PropertyAccessorArrayObjectTest extends PropertyAccessorCollectionTest
{
    #[\Override]
    protected function getContainer(array $array)
    {
        return new \ArrayObject($array);
    }
}
