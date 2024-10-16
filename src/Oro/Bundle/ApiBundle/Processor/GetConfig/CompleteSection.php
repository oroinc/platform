<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntityConfigInterface;

/**
 * The base class for processors that make sure that all supported filters and sorters
 * are added to API configuration and all of them are fully configured.
 */
abstract class CompleteSection implements ProcessorInterface
{
    protected DoctrineHelper $doctrineHelper;
    protected ConfigManager $configManager;

    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    protected function complete(
        EntityConfigInterface $section,
        string $entityClass,
        EntityDefinitionConfig $definition
    ): void {
        if (!$section->isExcludeAll()) {
            if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
                $this->completeFields($section, $entityClass, $definition);
            }
            $section->setExcludeAll();
        }

        $this->applyFieldExclusions($section, $definition);
    }

    abstract protected function completeFields(
        EntityConfigInterface $section,
        string $entityClass,
        EntityDefinitionConfig $definition
    ): void;

    protected function applyFieldExclusions(EntityConfigInterface $section, EntityDefinitionConfig $definition): void
    {
        $fields = $section->getFields();
        foreach ($fields as $fieldName => $sectionField) {
            if (!$sectionField->hasExcluded()) {
                $field = $definition->getField($fieldName);
                if (null !== $field && $field->isExcluded()) {
                    $sectionField->setExcluded();
                }
            }
        }
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return array [property path => data type, ...]
     */
    protected function getIndexedFields(ClassMetadata $metadata): array
    {
        return $this->doctrineHelper->getIndexedFields($metadata);
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return array [property path => data type, ...]
     */
    protected function getIndexedAssociations(ClassMetadata $metadata): array
    {
        $associations = $this->doctrineHelper->getIndexedAssociations($metadata);
        $enumFields = $this->getEnumFields($metadata->name);
        foreach ($enumFields as $propertyPath) {
            $associations[$propertyPath] = DataType::STRING;
        }

        return $associations;
    }

    protected function isCollectionValuedAssociation(ClassMetadata $metadata, string $propertyPath): bool
    {
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        $lastFieldName = array_pop($path);
        if (!empty($path)) {
            $parentMetadata = $this->doctrineHelper->findEntityMetadataByPath($metadata->name, $path);
            if (null === $parentMetadata) {
                return false;
            }
            $metadata = $parentMetadata;
        }

        if ($metadata->hasAssociation($lastFieldName)) {
            return $metadata->isCollectionValuedAssociation($lastFieldName);
        }

        if ($this->configManager->hasConfig($metadata->name, $lastFieldName)) {
            $fieldType = $this->configManager->getId('extend', $metadata->name, $lastFieldName)->getFieldType();
            if (ExtendHelper::isEnumerableType($fieldType)) {
                return ExtendHelper::isMultiEnumType($fieldType);
            }
        }

        return false;
    }

    private function getEnumFields(string $entityClass): array
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return [];
        }

        $fields = [];
        /** @var FieldConfigId[] $fieldConfigIds */
        $fieldConfigIds = $this->configManager->getIds('extend', $entityClass, true);
        foreach ($fieldConfigIds as $fieldConfigId) {
            $fieldType = $fieldConfigId->getFieldType();
            if (!ExtendHelper::isEnumerableType($fieldType)) {
                continue;
            }
            $fieldName = $fieldConfigId->getFieldName();
            if (!ExtendHelper::isFieldAccessible(
                $this->configManager->getFieldConfig('extend', $entityClass, $fieldName)
            )) {
                continue;
            }
            if (!$this->configManager->getFieldConfig('enum', $entityClass, $fieldName)->get('enum_code')) {
                continue;
            }
            $fields[] = $fieldName;
        }

        return $fields;
    }
}
