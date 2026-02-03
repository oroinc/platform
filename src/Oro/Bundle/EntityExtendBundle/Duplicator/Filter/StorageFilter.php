<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Duplicator\Filter;

use DeepCopy\Filter\Filter;
use DeepCopy\Reflection\ReflectionHelper;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Model\ExtendEntityStorage;

/**
 * DeepCopy Extended Entity Storage Filter
 */
class StorageFilter implements Filter
{
    #[\Override]
    public function apply($object, $property, $objectCopier): void
    {
        if (!$object instanceof ExtendEntityInterface) {
            return;
        }

        $reflectionProperty = ReflectionHelper::getProperty($object, $property);

        $oldStorage = $reflectionProperty->getValue($object);

        if ($oldStorage instanceof \ArrayObject) {
            $oldStorageIterator = new \ArrayIterator($oldStorage->getArrayCopy());
            $newStorageData = $objectCopier($oldStorageIterator);

            $newStorage = new ExtendEntityStorage(
                $newStorageData->getArrayCopy(),
                \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS
            );

            $reflectionProperty->setValue($object, $newStorage);
        }
    }
}
