<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Accessor;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The default implementation of a service to access entity data.
 */
class DefaultAccessor implements AccessorInterface
{
    private ?PropertyAccessorInterface $propertyAccessor = null;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'default';
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity, FieldMetadata $metadata)
    {
        return $metadata->isDefinedBySourceEntity();
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($entity, FieldMetadata $metadata)
    {
        if ($metadata->has('getter')) {
            $getter = $metadata->get('getter');

            return $entity->$getter();
        }

        return $this->getPropertyAccessor()
            ->getValue($entity, $this->getPropertyPath($metadata));
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($entity, FieldMetadata $metadata, $value)
    {
        if ($metadata->has('setter')) {
            $setter = $metadata->get('setter');
            $entity->$setter($value);

            return;
        }

        $this->getPropertyAccessor()
            ->setValue($entity, $this->getPropertyPath($metadata), $value);
    }

    protected function getPropertyPath(FieldMetadata $metadata): string
    {
        return $metadata->has('property_path')
            ? $metadata->get('property_path')
            : $metadata->getFieldName();
    }

    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
