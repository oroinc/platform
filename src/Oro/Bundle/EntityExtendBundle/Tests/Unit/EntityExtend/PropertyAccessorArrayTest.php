<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EntityExtend;

class PropertyAccessorArrayTest extends PropertyAccessorCollectionTest
{
    #[\Override]
    protected function getContainer(array $array)
    {
        return $array;
    }
}
