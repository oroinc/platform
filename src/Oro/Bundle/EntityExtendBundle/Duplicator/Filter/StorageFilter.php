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
    /**
     * @inheritDoc
     */
    public function apply($object, $property, $objectCopier): void
    {
        if (!$object instanceof ExtendEntityInterface) {
            return;
        }

        $reflectionProperty = ReflectionHelper::getProperty($object, $property);
        $reflectionProperty->setAccessible(true);

        $oldStorage = $reflectionProperty->getValue($object);

        if ($oldStorage instanceof \ArrayObject) {
            $oldStorage = (object) $oldStorage->getArrayCopy();
            $newStorage =(array) $objectCopier($oldStorage);

            $newStorage = new ExtendEntityStorage(
                $newStorage,
                \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS
            );
        }

        $reflectionProperty->setValue($object, $newStorage);
    }
}
