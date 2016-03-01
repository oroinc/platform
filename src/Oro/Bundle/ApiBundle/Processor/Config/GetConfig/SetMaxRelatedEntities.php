<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
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

        $maxRelatedEntities = $context->getMaxRelatedEntities();
        if (null === $maxRelatedEntities || $maxRelatedEntities < 0) {
            // there is no limit to the number of related entities
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $this->setLimits($context->getResult(), $entityClass, $maxRelatedEntities);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param int                    $limit
     */
    protected function setLimits(EntityDefinitionConfig $definition, $entityClass, $limit)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $fields   = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $propertyPath = $field->getPropertyPath() ?: $fieldName;
            $path         = ConfigUtil::explodePropertyPath($propertyPath);
            if (count($path) === 1) {
                $this->setFieldLimit($field, $metadata, $propertyPath, $limit);
            } else {
                $linkedField    = array_pop($path);
                $linkedMetadata = $this->doctrineHelper->findEntityMetadataByPath($entityClass, $path);
                if (null !== $linkedMetadata) {
                    $this->setFieldLimit($field, $linkedMetadata, $linkedField, $limit);
                }
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param ClassMetadata               $metadata
     * @param string                      $fieldName
     * @param int                         $limit
     */
    protected function setFieldLimit(
        EntityDefinitionFieldConfig $field,
        ClassMetadata $metadata,
        $fieldName,
        $limit
    ) {
        if ($metadata->hasAssociation($fieldName)) {
            if ($metadata->isCollectionValuedAssociation($fieldName)) {
                $targetEntity = $field->getOrCreateTargetEntity();
                if (!$targetEntity->hasMaxResults()) {
                    $targetEntity->setMaxResults($limit);
                }
            }
            if ($field->hasTargetEntity()) {
                $this->setLimits($field->getTargetEntity(), $metadata->name, $limit);
            }
        }
    }
}
