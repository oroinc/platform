<?php

namespace Oro\Bundle\EntityMergeBundle\Model\EntityAccessor;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class DefaultEntityAccessor implements EntityAccessorInterface
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * {@inheritdoc}
     */
    public function getValue($entity, FieldMetadata $metadata)
    {
        if ($metadata->has('getter')) {
            $getter = $metadata->get('getter');
            return $entity->$getter();
        }

        return $this->getPropertyAccessor()->getValue($entity, $this->getPropertyPath($metadata));
    }

    /**
     * @return PropertyAccessor
     */
    public function getName()
    {
        return 'default';
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

        $this->getPropertyAccessor()->setValue($entity, $this->getPropertyPath($metadata), $value);
    }

    /**
     * @param FieldMetadata $metadata
     * @return string
     */
    protected function getPropertyPath(FieldMetadata $metadata)
    {
        return $metadata->has('property_path') ? $metadata->get('property_path') : $metadata->getFieldName();
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }
        return $this->propertyAccessor;
    }
}
