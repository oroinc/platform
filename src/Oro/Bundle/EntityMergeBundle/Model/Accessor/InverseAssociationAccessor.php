<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Accessor;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class InverseAssociationAccessor implements AccessorInterface
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Checks if this class supports accessing entity
     *
     * @param string        $entity
     * @param FieldMetadata $metadata
     * @return string
     */
    public function supports($entity, FieldMetadata $metadata)
    {
        return $metadata->hasDoctrineMetadata() && !$metadata->getDoctrineMetadata()->isMappedBySourceEntity();
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($entity, FieldMetadata $metadata)
    {
        $doctrineMetadata = $metadata->getDoctrineMetadata();
        $fieldName        = $doctrineMetadata->getFieldName();
        $className        = $doctrineMetadata->get('sourceEntity');

        return $this->doctrineHelper->getEntityRepository($className)->findBy([$fieldName => $entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($entity, FieldMetadata $metadata, $value)
    {
        $oldRelatedEntities = array();

        foreach ($this->getValue($entity, $metadata) as $oldRelatedEntity) {
            $oldRelatedEntities[$this->doctrineHelper->getEntityIdentifierValue($oldRelatedEntity)] = $oldRelatedEntity;
        }

        foreach ($value as $relatedEntity) {
            $this->setRelatedEntityValue($relatedEntity, $metadata, $entity);
            unset($oldRelatedEntities[$this->doctrineHelper->getEntityIdentifierValue($relatedEntity)]);
        }

        foreach ($oldRelatedEntities as $oldRelatedEntity) {
            $this->setRelatedEntityValue($oldRelatedEntity, $metadata, null);
        }
    }

    /**
     * @param object $relatedEntity
     * @param FieldMetadata $metadata
     * @param object $value
     */
    protected function setRelatedEntityValue($relatedEntity, FieldMetadata $metadata, $value)
    {
        if ($metadata->has('setter')) {
            $setter = $metadata->get('setter');
            $relatedEntity->$setter($value);
        } else {
            $this->getPropertyAccessor()
                ->setValue(
                    $relatedEntity,
                    $this->getPropertyPath($metadata),
                    $value
                );
        }
    }

    /**
     * @param FieldMetadata $metadata
     * @return string
     */
    protected function getPropertyPath(FieldMetadata $metadata)
    {
        if ($metadata->has('property_path')) {
            return $metadata->get('property_path');
        }

        if ($metadata->hasDoctrineMetadata()) {
            return $metadata->getDoctrineMetadata()->getFieldName();
        }

        return $metadata->getFieldName();
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

    /**
     * @return string
     */
    public function getName()
    {
        return 'inverse_association';
    }
}
