<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\EntityClassTransformerInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Excludes fields according to requested fieldset.
 */
class FilterFieldsByExtra implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassTransformerInterface */
    protected $entityClassTransformer;

    /**
     * @param DoctrineHelper                  $doctrineHelper
     * @param EntityClassTransformerInterface $entityClassTransformer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassTransformerInterface $entityClassTransformer
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->entityClassTransformer = $entityClassTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $definition    = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $filtersConfig = $context->get(FilterFieldsConfigExtra::NAME);

        $filtersConfig = $this->filterFieldsForRootEntity($definition, $entityClass, $filtersConfig);
        $this->filterFieldsForRelatedEntities($definition, $entityClass, $filtersConfig);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param array                  $filtersConfig
     *
     * @return array The $filtersConfig without processed filters
     */
    protected function filterFieldsForRootEntity(
        EntityDefinitionConfig $definition,
        $entityClass,
        $filtersConfig
    ) {
        $entityAlias  = $this->entityClassTransformer->transform($entityClass);
        $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        if (array_key_exists($entityAlias, $filtersConfig)) {
            $allowedFields = $filtersConfig[$entityAlias];
            $fields        = $definition->getFields();
            foreach ($fields as $fieldName => $field) {
                if (!in_array($fieldName, $allowedFields, true)
                    && !in_array($fieldName, $idFieldNames, true)
                    && !ConfigUtil::isMetadataProperty($field->getPropertyPath() ?: $fieldName)
                ) {
                    $field->setExcluded();
                }
            }

            unset($filtersConfig[$entityAlias]);
        }

        return $filtersConfig;
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param array                  $filtersConfig
     *
     * @return array The $filtersConfig without processed filters
     */
    protected function filterFieldsForRelatedEntities(
        EntityDefinitionConfig $definition,
        $entityClass,
        $filtersConfig
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $associationsMapping = $metadata->getAssociationMappings();
        foreach ($associationsMapping as $associationName => $mapping) {
            if (!array_key_exists($associationName, $filtersConfig)) {
                continue;
            }
            $field = $definition->getField($associationName);
            if (null === $field || !$field->hasTargetEntity()) {
                continue;
            }

            $idFieldNames  = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($mapping['targetEntity']);
            $allowedFields = $filtersConfig[$associationName];
            $targetFields  = $field->getTargetEntity()->getFields();
            foreach ($targetFields as $targetFieldName => $targetField) {
                if (!in_array($targetFieldName, $allowedFields, true)
                    && !in_array($targetFieldName, $idFieldNames, true)
                    && !ConfigUtil::isMetadataProperty($targetField->getPropertyPath() ?: $targetFieldName)
                ) {
                    $targetField->setExcluded();
                }
            }

            unset($filtersConfig[$associationName]);
        }

        return $filtersConfig;
    }
}
