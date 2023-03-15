<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EntityExtend;

class PropertyAccessorArrayTest extends PropertyAccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return $array;
    }
}
