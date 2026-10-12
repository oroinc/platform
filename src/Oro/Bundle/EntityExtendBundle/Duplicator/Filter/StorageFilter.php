<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Duplicator\Filter;

use DeepCopy\Filter\Filter;
use DeepCopy\Reflection\ReflectionHelper;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Model\ExtendEntityStorage;
use Oro\Component\Duplicator\Filter\SourceBagRetentionTrait;
use Oro\Component\Duplicator\PropertyBag;

/**
 * DeepCopy Extended Entity Storage Filter
 */
class StorageFilter implements Filter
{
    use SourceBagRetentionTrait;

    #[\Override]
    public function apply($object, $property, $objectCopier): void
    {
        if (!$object instanceof ExtendEntityInterface) {
            return;
        }

        $reflectionProperty = ReflectionHelper::getProperty($object, $property);
        $oldStorage = $reflectionProperty->getValue($object);

        if ($oldStorage instanceof \ArrayObject) {
            $this->unsetSerializedNormalizedData($oldStorage);

            $bag = new PropertyBag(ClassUtils::getClass($object), $oldStorage->getArrayCopy());
            $this->retainSourceBag($bag);
            $copiedBag = $objectCopier($bag);

            $newStorage = new ExtendEntityStorage(
                $copiedBag->toArray(),
                \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS
            );

            $reflectionProperty->setValue($object, $newStorage);
        }
    }

    private function unsetSerializedNormalizedData(\ArrayObject $storage): void
    {
        if ($storage->offsetExists('serialized_normalized')) {
            $storage->offsetUnset('serialized_normalized');
        }
    }
}
