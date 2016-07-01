<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Updates configuration to ask the EntitySerializer that the entity class should be returned
 * together with related entity data in case if the entity implemented using Doctrine table inheritance.
 */
class SetTypeForTableInheritanceRelations implements ProcessorInterface
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

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $this->updateRelations($definition, $entityClass);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    protected function updateRelations(EntityDefinitionConfig $definition, $entityClass)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $fields   = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $propertyPath = $field->getPropertyPath() ?: $fieldName;
            if (!$metadata->hasAssociation($propertyPath)) {
                continue;
            }

            $mapping = $metadata->getAssociationMapping($propertyPath);
            $targetClass = $mapping['targetEntity'];
            $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass);
            if ($targetMetadata->inheritanceType === ClassMetadata::INHERITANCE_TYPE_NONE) {
                continue;
            }

            if (!$field->getTargetClass()) {
                $field->setTargetClass($targetClass);
            }
            $targetEntity = $field->getOrCreateTargetEntity();
            if (!$targetEntity->hasField(ConfigUtil::CLASS_NAME)) {
                $targetEntity->addField(ConfigUtil::CLASS_NAME);
            }
        }
    }
}
