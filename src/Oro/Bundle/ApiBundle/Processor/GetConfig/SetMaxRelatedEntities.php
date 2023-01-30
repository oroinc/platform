<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the limit to the maximum number of the related entities.
 */
class SetMaxRelatedEntities implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $maxRelatedEntities = $context->getMaxRelatedEntities();
        if (null === $maxRelatedEntities || $maxRelatedEntities < 0) {
            // there is no limit to the number of related entities
            return;
        }

        $entityClass = $context->getClassName();
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $this->setEntityLimits(
                $definition,
                $this->doctrineHelper->getEntityMetadataForClass($entityClass),
                $maxRelatedEntities
            );
        } else {
            $this->setObjectLimits($definition, $maxRelatedEntities);
        }
    }

    private function setEntityLimits(EntityDefinitionConfig $definition, ClassMetadata $metadata, int $limit): void
    {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $propertyPath = $field->getPropertyPath($fieldName);
            if ($metadata->hasAssociation($propertyPath)) {
                $this->setEntityFieldLimit($field, $metadata, $propertyPath, $limit);
            }
        }
    }

    private function setEntityFieldLimit(
        EntityDefinitionFieldConfig $field,
        ClassMetadata $metadata,
        string $fieldName,
        int $limit
    ): void {
        if ($metadata->isCollectionValuedAssociation($fieldName)) {
            $targetEntity = $field->getOrCreateTargetEntity();
            if (!$targetEntity->hasMaxResults()) {
                if (!DataType::isAssociationAsField($field->getDataType())) {
                    $targetEntity->setMaxResults($limit);
                }
            } elseif ($targetEntity->getMaxResults() < 0) {
                $targetEntity->setMaxResults(null);
            }
        }
        if ($field->hasTargetEntity()) {
            $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass(
                $metadata->getAssociationTargetClass($fieldName)
            );
            $this->setEntityLimits($field->getTargetEntity(), $targetMetadata, $limit);
        }
    }

    private function setObjectLimits(EntityDefinitionConfig $definition, int $limit): void
    {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->getTargetClass()) {
                $this->setObjectFieldLimit($field, $limit);
            }
        }
    }

    private function setObjectFieldLimit(EntityDefinitionFieldConfig $field, int $limit): void
    {
        if ($field->isCollectionValuedAssociation()) {
            $targetEntity = $field->getOrCreateTargetEntity();
            if (!$targetEntity->hasMaxResults()) {
                if (!DataType::isAssociationAsField($field->getDataType())) {
                    $targetEntity->setMaxResults($limit);
                }
            } elseif ($targetEntity->getMaxResults() < 0) {
                $targetEntity->setMaxResults(null);
            }
        }
        if ($field->hasTargetEntity()) {
            $this->setObjectLimits($field->getTargetEntity(), $limit);
        }
    }
}
