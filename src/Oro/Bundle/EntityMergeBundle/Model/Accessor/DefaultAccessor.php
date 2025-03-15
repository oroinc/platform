<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Accessor;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The default implementation of a service to access entity data.
 */
class DefaultAccessor implements AccessorInterface
{
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor
    ) {
    }

    #[\Override]
    public function getName()
    {
        return 'default';
    }

    #[\Override]
    public function supports($entity, FieldMetadata $metadata)
    {
        return $metadata->isDefinedBySourceEntity();
    }

    #[\Override]
    public function getValue($entity, FieldMetadata $metadata)
    {
        if ($metadata->has('getter')) {
            $getter = $metadata->get('getter');

            return $entity->$getter();
        }

        return $this->propertyAccessor->getValue($entity, $this->getPropertyPath($metadata));
    }

    #[\Override]
    public function setValue($entity, FieldMetadata $metadata, $value)
    {
        if ($metadata->has('setter')) {
            $setter = $metadata->get('setter');
            $entity->$setter($value);

            return;
        }

        $this->propertyAccessor->setValue($entity, $this->getPropertyPath($metadata), $value);
    }

    private function getPropertyPath(FieldMetadata $metadata): string
    {
        return $metadata->has('property_path')
            ? $metadata->get('property_path')
            : $metadata->getFieldName();
    }
}
