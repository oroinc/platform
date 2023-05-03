<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Util\EntityDataAccessor;
use Oro\Component\EntitySerializer\Tests\Unit\EntityDataAccessorTest as BaseTest;

class EntityDataAccessorTest extends BaseTest
{
    protected function createEntityDataAccessor(): EntityDataAccessor
    {
        return new EntityDataAccessor();
    }
}
