<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Component\EntitySerializer\EntityDataAccessor as BaseEntityDataAccessor;

/**
 * Reads property values from entity objects or arrays.
 */
class EntityDataAccessor extends BaseEntityDataAccessor
{
    /**
     * {@inheritdoc}
     */
    public function hasGetter($className, $property): bool
    {
        $result = parent::hasGetter($className, $property)
            || $this->getReflectionClass($className)->hasMethod('__get');

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

        if (!$result && is_object($object)
            && $this->getReflectionClass(get_class($object))->hasMethod('__get')
        ) {
            try {
                [$result, $value] = [true, $object->__get($property)];
            } catch (\Exception $e) {
            }
        }

        if (!$result && $object instanceof \ArrayAccess && $object->offsetExists($property)) {
            $value = $object->offsetGet($property);
            $result = true;
        }

        return $result;
    }
}
