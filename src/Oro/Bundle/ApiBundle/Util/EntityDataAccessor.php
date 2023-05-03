<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Component\EntitySerializer\EntityDataAccessor as BaseEntityDataAccessor;

/**
 * Reads property values from entity objects or arrays.
 */
class EntityDataAccessor extends BaseEntityDataAccessor
{
    /**
     * {@inheritDoc}
     */
    protected function createReflectionClass(string $className): \ReflectionClass
    {
        return new EntityReflectionClass($className);
    }
}
