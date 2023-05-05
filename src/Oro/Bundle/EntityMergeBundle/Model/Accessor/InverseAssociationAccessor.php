<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Accessor;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The inverse associations entity data accessor.
 */
class InverseAssociationAccessor implements AccessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private ?PropertyAccessorInterface $propertyAccessor = null;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'inverse_association';
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity, FieldMetadata $metadata)
    {
        return
            !$metadata->isDefinedBySourceEntity()
            && $metadata->hasDoctrineMetadata()
            && $this->isToOneAssociation($metadata->getDoctrineMetadata());
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($entity, FieldMetadata $metadata)
    {
        $doctrineMetadata = $metadata->getDoctrineMetadata();
        $fieldName = $doctrineMetadata->getFieldName();
        $className = $doctrineMetadata->get('sourceEntity');

        return $this->doctrineHelper->getEntityRepository($className)->findBy([$fieldName => $entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($entity, FieldMetadata $metadata, $value)
    {
        $oldRelatedEntities = [];

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

    private function isToOneAssociation(DoctrineMetadata $metadata): bool
    {
        return $metadata->isManyToOne() || $metadata->isOneToOne();
    }

    private function setRelatedEntityValue(object $relatedEntity, FieldMetadata $metadata, ?object $value): void
    {
        if ($metadata->has('setter')) {
            $setter = $metadata->get('setter');
            $relatedEntity->$setter($value);
        } else {
            try {
                $this->getPropertyAccessor()
                    ->setValue($relatedEntity, $metadata->getDoctrineMetadata()->getFieldName(), $value);
            } catch (NoSuchPropertyException $e) {
                // If setter is not exist
                $reflection = new \ReflectionProperty(
                    ClassUtils::getClass($relatedEntity),
                    $metadata->getDoctrineMetadata()->getFieldName()
                );
                $reflection->setAccessible(true);
                $reflection->setValue($relatedEntity, $value);
            }
        }
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
