<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\EntitySerializer\EntityConfigInterface;

/**
 * Makes sure that the sorters configuration contains all supported sorters
 * and all sorters are fully configured.
 */
class CompleteSorters extends CompleteSection
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $this->complete($context->getSorters(), $context->getClassName(), $context->getResult());
    }

    /**
     * {@inheritdoc}
     */
    protected function completeFields(
        EntityConfigInterface $section,
        string $entityClass,
        EntityDefinitionConfig $definition
    ): void {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $this->completeSortersForIdentifierFields($section, $definition);
        $this->completeSortersForFields($section, $metadata, $definition);
        $this->completeSortersForAssociations($section, $metadata, $definition);
    }

    private function completeSortersForIdentifierFields(
        EntityConfigInterface $sorters,
        EntityDefinitionConfig $definition
    ): void {
        $idFieldNames = $definition->getIdentifierFieldNames();
        foreach ($idFieldNames as $fieldName) {
            if ($definition->hasField($fieldName)) {
                $sorters->getOrAddField($fieldName);
            }
        }
    }

    private function completeSortersForFields(
        EntityConfigInterface $sorters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ): void {
        $fields = $this->getIndexedFields($metadata);
        foreach ($fields as $propertyPath => $dataType) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName && !$sorters->hasField($fieldName)) {
                $sorters->addField($fieldName);
            }
        }
    }

    private function completeSortersForAssociations(
        EntityConfigInterface $sorters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ): void {
        $indexedAssociations = $this->getIndexedAssociations($metadata);
        foreach ($indexedAssociations as $propertyPath => $dataType) {
            $sorter = $sorters->findField($propertyPath, true);
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName && (null !== $sorter || !$sorters->hasField($fieldName))) {
                if (null === $sorter) {
                    $sorter = $sorters->addField($fieldName);
                }
                if ($this->isCollectionValuedAssociation($metadata, $propertyPath)) {
                    if ($metadata->hasAssociation($propertyPath)) {
                        $targetIdIdFieldName = $this->doctrineHelper
                            ->getEntityMetadataForClass($metadata->getAssociationTargetClass($propertyPath))
                            ->getSingleIdentifierFieldName();
                        $sorter->setPropertyPath($propertyPath . ConfigUtil::PATH_DELIMITER . $targetIdIdFieldName);
                    } elseif ($this->isEnumAssociation($definition, $propertyPath)) {
                        $sorter->setPropertyPath($propertyPath . ConfigUtil::PATH_DELIMITER . 'id');
                    }
                }
            }
        }
    }

    private function isEnumAssociation(EntityDefinitionConfig $definition, string $propertyName): bool
    {
        $field = $definition->findField($propertyName, true);
        if (null === $field) {
            return false;
        }
        $targetClass = $field->getTargetClass();
        if (!$targetClass || null === $field->getTargetEntity()) {
            return false;
        }

        return ExtendHelper::isOutdatedEnumOptionEntity($targetClass);
    }
}
