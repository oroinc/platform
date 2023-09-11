<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\JsonApi;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\UpsertConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\AbstractAddStatusCodes;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Fills the configuration of the upsert operation.
 */
class CompleteUpsertConfig extends AbstractAddStatusCodes
{
    public const OPERATION_NAME = 'complete_upsert_config';

    private DoctrineHelper $doctrineHelper;
    private EntityIdHelper $entityIdHelper;

    public function __construct(DoctrineHelper $doctrineHelper, EntityIdHelper $entityIdHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityIdHelper = $entityIdHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // already processed
            return;
        }

        if (!$context->hasExtra(FilterIdentifierFieldsConfigExtra::NAME)) {
            $definition = $context->getResult();
            $upsertConfig = $definition->getUpsertConfig();
            if ($upsertConfig->isEnabled()) {
                $entityClass = $context->getClassName();
                if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
                    /** @var ClassMetadata $metadata */
                    $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
                    if (!$upsertConfig->hasAllowedById()) {
                        $upsertConfig->setAllowedById(
                            $this->isEntityIdCanBeUsedByUpsertOperationToFindEntity($definition, $metadata)
                        );
                    }
                    $this->addFieldsToUpsertConfig($upsertConfig, $definition, $metadata);
                }
                if (!$upsertConfig->isAllowedById() && !$upsertConfig->getFields()) {
                    $upsertConfig->setEnabled(false);
                } elseif (!$upsertConfig->hasEnabled()) {
                    $upsertConfig->setEnabled(true);
                }
            } else {
                $upsertConfig->setAllowedById(false);
                $upsertConfig->replaceFields([]);
            }
        }

        $context->setProcessed(self::OPERATION_NAME);
    }

    private function isEntityIdCanBeUsedByUpsertOperationToFindEntity(
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata
    ): bool {
        if (!$definition->getIdentifierFieldNames()) {
            return false;
        }
        if (!$metadata->usesIdGenerator()) {
            return true;
        }

        return !$this->entityIdHelper->isEntityIdentifierEqual($metadata->getIdentifierFieldNames(), $definition);
    }

    private function addFieldsToUpsertConfig(
        UpsertConfig $upsertConfig,
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata
    ): void {
        $this->addFieldsToUpsertConfigByUniqueColumns($upsertConfig, $definition, $metadata);
        $this->addFieldsToUpsertConfigByUniqueConstraints($upsertConfig, $definition, $metadata);
    }

    private function addFieldsToUpsertConfigByUniqueColumns(
        UpsertConfig $upsertConfig,
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata
    ): void {
        $propertyNames = $metadata->getFieldNames();
        foreach ($propertyNames as $propertyName) {
            if (!$metadata->isUniqueField($propertyName)) {
                continue;
            }
            $fieldName = $definition->findFieldNameByPropertyPath($propertyName);
            if ($fieldName && [$fieldName] !== $definition->getIdentifierFieldNames()) {
                $upsertConfig->addFields([$fieldName]);
            }
        }
    }

    private function addFieldsToUpsertConfigByUniqueConstraints(
        UpsertConfig $upsertConfig,
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata
    ): void {
        if (isset($metadata->table['uniqueConstraints'])) {
            foreach ($metadata->table['uniqueConstraints'] as $uniqueConstraint) {
                $fieldNames = [];
                foreach ($uniqueConstraint['columns'] as $propertyName) {
                    $fieldName = $definition->findFieldNameByPropertyPath($propertyName);
                    if (!$fieldName) {
                        $fieldNames = [];
                        break;
                    }
                    $fieldNames[] = $fieldName;
                }
                if ($fieldNames && $fieldNames !== $definition->getIdentifierFieldNames()) {
                    $upsertConfig->addFields($fieldNames);
                }
            }
        }
    }
}
