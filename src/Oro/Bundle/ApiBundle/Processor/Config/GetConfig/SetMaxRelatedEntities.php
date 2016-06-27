<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Sets the limit to the maximum number of the related entities.
 */
class SetMaxRelatedEntities implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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

    /**
     * @param EntityDefinitionConfig $definition
     * @param ClassMetadata          $metadata
     * @param int                    $limit
     */
    protected function setEntityLimits(EntityDefinitionConfig $definition, ClassMetadata $metadata, $limit)
    {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $propertyPath = $field->getPropertyPath() ?: $fieldName;
            if ($metadata->hasAssociation($propertyPath)) {
                $this->setEntityFieldLimit($field, $metadata, $propertyPath, $limit);
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param ClassMetadata               $metadata
     * @param string                      $fieldName
     * @param int                         $limit
     */
    protected function setEntityFieldLimit(
        EntityDefinitionFieldConfig $field,
        ClassMetadata $metadata,
        $fieldName,
        $limit
    ) {
        if ($metadata->isCollectionValuedAssociation($fieldName)) {
            $targetEntity = $field->getOrCreateTargetEntity();
            if (!$targetEntity->hasMaxResults()) {
                $targetEntity->setMaxResults($limit);
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

    /**
     * @param EntityDefinitionConfig $definition
     * @param int                    $limit
     */
    protected function setObjectLimits(EntityDefinitionConfig $definition, $limit)
    {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->getTargetClass()) {
                $this->setObjectFieldLimit($field, $limit);
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param int                         $limit
     */
    protected function setObjectFieldLimit(EntityDefinitionFieldConfig $field, $limit)
    {
        if ($field->isCollectionValuedAssociation()) {
            $targetEntity = $field->getOrCreateTargetEntity();
            if (!$targetEntity->hasMaxResults()) {
                $targetEntity->setMaxResults($limit);
            } elseif ($targetEntity->getMaxResults() < 0) {
                $targetEntity->setMaxResults(null);
            }
        }
        if ($field->hasTargetEntity()) {
            $this->setObjectLimits($field->getTargetEntity(), $limit);
        }
    }
}
