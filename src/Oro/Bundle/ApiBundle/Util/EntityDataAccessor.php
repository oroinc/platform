<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Component\EntitySerializer\EntityDataAccessor as BaseEntityDataAccessor;

class EntityDataAccessor extends BaseEntityDataAccessor
{
    /**
     * {@inheritdoc}
     */
    public function hasGetter($className, $property)
    {
        $result = parent::hasGetter($className, $property);
        if (!$result && is_a($className, \ArrayAccess::class, true)) {
            $result = true;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function tryGetValue($object, $property, &$value)
    {
        $result = parent::tryGetValue($object, $property, $value);
        if (!$result && $object instanceof \ArrayAccess && $object->offsetExists($property)) {
            $value = $object->offsetGet($property);
            $result = true;
        }

        return $result;
    }
}
