<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntityConfigInterface;
use Oro\Component\EntitySerializer\FieldConfigInterface;

/**
 * The base class for processors that do normalization of filters and sorters, such as:
 * * remove all elements marked as excluded
 * * update the property path attribute for existing elements
 * * extract elements from the definitions of related entities
 * * remove duplicated elements
 */
abstract class NormalizeSection implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    protected function normalize(
        EntityConfigInterface $section,
        string $sectionName,
        string $entityClass,
        EntityDefinitionConfig $definition
    ): void {
        if ($section->hasFields()) {
            $this->removeExcludedFieldsAndUpdatePropertyPath($section, $definition);
        }
        $this->collect($section, $sectionName, $entityClass, $definition);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function removeExcludedFieldsAndUpdatePropertyPath(
        EntityConfigInterface $section,
        EntityDefinitionConfig $definition
    ): void {
        $fields = $section->getFields();
        $toRemoveFieldNames = [];
        $toAddFields = [];
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                $toRemoveFieldNames[] = $fieldName;
            } elseif (!$field->hasPropertyPath()) {
                if ($definition->hasField($fieldName)) {
                    $propertyPath = $definition->getField($fieldName)->getPropertyPath();
                    if ($propertyPath) {
                        $field->setPropertyPath($propertyPath);
                    }
                } else {
                    $definitionFieldName = $definition->findFieldNameByPropertyPath($fieldName);
                    if ($definitionFieldName) {
                        $propertyPath = $definition->getField($definitionFieldName)->getPropertyPath();
                        if ($propertyPath) {
                            $field->setPropertyPath($propertyPath);
                            $toRemoveFieldNames[] = $fieldName;
                            $toAddFields[$definitionFieldName] = $field;
                        }
                    }
                }
            }
        }
        foreach ($toRemoveFieldNames as $fieldName) {
            $section->removeField($fieldName);
        }
        foreach ($toAddFields as $fieldName => $field) {
            $section->addField($fieldName, $field);
        }
    }

    private function collect(
        EntityConfigInterface $section,
        string $sectionName,
        string $entityClass,
        EntityDefinitionConfig $definition
    ): void {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->hasTargetEntity()) {
                $targetEntity = $field->getTargetEntity();
                if ($targetEntity->has($sectionName)) {
                    $this->collectNested(
                        $section,
                        $sectionName,
                        $entityClass,
                        $targetEntity,
                        $this->buildPrefix($fieldName),
                        $this->buildPrefix($field->getPropertyPath($fieldName))
                    );
                    $targetEntity->remove($sectionName);
                }
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function collectNested(
        EntityConfigInterface $section,
        string $sectionName,
        string $entityClass,
        EntityDefinitionConfig $definition,
        string $fieldPrefix,
        string $pathPrefix
    ): void {
        /** @var FieldConfigInterface[] $sectionFields */
        $sectionFields = $definition->get($sectionName)->getFields();
        if (empty($sectionFields)) {
            return;
        }

        $path = substr($pathPrefix, 0, -1);
        $metadata = $this->getEntityMetadata($entityClass, $path);
        $isCollectionValuedAssociation = $this->isCollectionValuedAssociation(
            $metadata,
            $this->getLastFieldName($path)
        );
        foreach ($sectionFields as $fieldName => $sectionField) {
            $field = $definition->getField($fieldName);
            $propertyPath = $this->getPropertyPath($sectionField, $fieldName, $field);
            $fieldPath = $pathPrefix . $propertyPath;

            // skip identifier fields to avoid duplicates
            $targetMetadata = $this->getEntityMetadata($entityClass, $fieldPath);
            $targetFieldName = $this->getLastFieldName($fieldPath);
            if (null !== $targetMetadata
                && \in_array($targetFieldName, $targetMetadata->getIdentifierFieldNames(), true)
            ) {
                continue;
            }

            if (!$isCollectionValuedAssociation
                && !$this->isCollectionValuedAssociation($targetMetadata, $targetFieldName)
            ) {
                $fieldKey = $fieldPrefix . $fieldName;
                if (!$section->hasField($fieldKey)) {
                    $section->addField($fieldKey, $sectionField);
                }
                if ($fieldPath !== $fieldKey) {
                    $sectionField->setPropertyPath($fieldPath);
                } elseif ($sectionField->hasPropertyPath()) {
                    $sectionField->setPropertyPath();
                }
            }

            if (null !== $field && $field->hasTargetEntity()) {
                $targetEntity = $field->getTargetEntity();
                if ($targetEntity->has($sectionName)) {
                    $this->collectNested(
                        $section,
                        $sectionName,
                        $entityClass,
                        $targetEntity,
                        $this->buildPrefix($fieldName, $fieldPrefix),
                        $this->buildPrefix($propertyPath, $pathPrefix)
                    );
                    $targetEntity->remove($sectionName);
                }
            }
        }
    }

    private function getPropertyPath(
        FieldConfigInterface $sectionField,
        string $fieldName,
        EntityDefinitionFieldConfig $field = null
    ): string {
        $propertyPath = $fieldName;
        if ($sectionField->hasPropertyPath()) {
            $propertyPath = $sectionField->getPropertyPath();
        } elseif (null !== $field && $field->hasPropertyPath()) {
            $propertyPath = $field->getPropertyPath();
        }

        return $propertyPath;
    }

    private function buildPrefix(string $name, ?string $currentPrefix = null): string
    {
        return (null !== $currentPrefix ? $currentPrefix . $name : $name) . ConfigUtil::PATH_DELIMITER;
    }

    private function isCollectionValuedAssociation(?ClassMetadata $metadata, string $fieldName): bool
    {
        return
            null !== $metadata
            && $metadata->hasAssociation($fieldName)
            && $metadata->isCollectionValuedAssociation($fieldName);
    }

    private function getEntityMetadata(string $entityClass, string $propertyPath): ?ClassMetadata
    {
        $metadata = null;
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $path = ConfigUtil::explodePropertyPath($propertyPath);
            array_pop($path);
            $metadata = !empty($path)
                ? $this->doctrineHelper->findEntityMetadataByPath($entityClass, $path)
                : $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        }

        return $metadata;
    }

    private function getLastFieldName(string $propertyPath): string
    {
        $path = ConfigUtil::explodePropertyPath($propertyPath);

        return end($path);
    }
}
