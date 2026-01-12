<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\EntitySerializer\EntityConfigInterface;

/**
 * Makes sure that the filters configuration contains all supported filters
 * and all filters are fully configured.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CompleteFilters extends CompleteSection
{
    /** @var array [data type => true, ...] */
    private array $disallowArrayDataTypes;
    /** @var array [data type => true, ...] */
    private array $disallowRangeDataTypes;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager  $configManager
     * @param string[]       $disallowArrayDataTypes
     * @param string[]       $disallowRangeDataTypes
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        array $disallowArrayDataTypes,
        array $disallowRangeDataTypes
    ) {
        parent::__construct($doctrineHelper, $configManager);
        $this->disallowArrayDataTypes = array_fill_keys($disallowArrayDataTypes, true);
        $this->disallowRangeDataTypes = array_fill_keys($disallowRangeDataTypes, true);
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $this->complete($context->getFilters(), $context->getClassName(), $context->getResult());
    }

    #[\Override]
    protected function completeFields(
        EntityConfigInterface $section,
        string $entityClass,
        EntityDefinitionConfig $definition
    ): void {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        /** @var FiltersConfig $section */
        $this->completePreConfiguredFilters($section, $metadata, $definition);
        $this->completeFiltersForIdentifierFields($section, $metadata, $definition);
        $this->completeFiltersForFields($section, $metadata, $definition);
        $this->completeFiltersForAssociations($section, $metadata, $definition);
        $this->completeFiltersForExtendedAssociations($section, $metadata, $definition);
    }

    private function completePreConfiguredFilters(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ): void {
        $filtersFields = $filters->getFields();
        foreach ($filtersFields as $fieldName => $filter) {
            if ($filter->hasType()) {
                if (!$filter->hasExcluded()) {
                    $filter->setExcluded(false);
                }
                continue;
            }
            $this->completePreConfiguredFilter($fieldName, $filter, $metadata, $definition);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function completePreConfiguredFilter(
        string $fieldName,
        FilterFieldConfig $filter,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ): void {
        if (!$filter->hasPropertyPath()) {
            $field = $definition->getField($fieldName);
            if (null !== $field) {
                $propertyPath = $field->getPropertyPath();
                if (null !== $propertyPath) {
                    $filter->setPropertyPath($propertyPath);
                }
                if (!$filter->hasDataType()) {
                    $dataType = $field->getDataType();
                    if ($dataType) {
                        $filter->setDataType($dataType);
                    }
                }
            }
        }
        $propertyPath = $filter->getPropertyPath($fieldName);
        if (!$filter->hasDataType()) {
            $dataType = $this->getFieldDataType($metadata, $propertyPath);
            if ($dataType) {
                $filter->setDataType($dataType);
            }
        }
        if (
            !$filter->hasCollection()
            && $propertyPath !== $fieldName
            && $this->isCollectionValuedAssociation($metadata, $propertyPath)
        ) {
            $filter->setIsCollection(true);
        }
        $this->setFilterArrayAllowed($filter);
        $this->setFilterRangeAllowed($filter);
    }

    private function completeFiltersForIdentifierFields(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ): void {
        $idFieldNames = $definition->getIdentifierFieldNames();
        foreach ($idFieldNames as $fieldName) {
            $field = $definition->getField($fieldName);
            if (null !== $field) {
                $filter = $filters->getOrAddField($fieldName);
                if (!$filter->hasDataType()) {
                    $dataType = $field->getDataType();
                    if (!$dataType) {
                        $dataType = $this->doctrineHelper->getFieldDataType(
                            $metadata,
                            $field->getPropertyPath($fieldName)
                        );
                        if (!$dataType) {
                            $dataType = DataType::STRING;
                        }
                    }
                    $filter->setDataType($dataType);
                }
                $this->setFilterArrayAllowed($filter);
                $this->setFilterRangeAllowed($filter);
            }
        }
    }

    private function completeFiltersForFields(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ): void {
        $fields = $this->getIndexedFields($metadata);
        foreach ($fields as $propertyPath => $dataType) {
            $filter = $filters->findField($propertyPath, true);
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName && (null !== $filter || !$filters->hasField($fieldName))) {
                if (null === $filter) {
                    $filter = $filters->addField($fieldName);
                }
                if (!$filter->hasDataType()) {
                    $filter->setDataType($dataType);
                }
                $this->setFilterArrayAllowed($filter);
                $this->setFilterRangeAllowed($filter);
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function completeFiltersForAssociations(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ): void {
        $indexedAssociations = $this->getIndexedAssociations($metadata);
        foreach ($indexedAssociations as $propertyPath => $dataType) {
            $filter = $filters->findField($propertyPath, true);
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName && (null !== $filter || !$filters->hasField($fieldName))) {
                if (null === $filter) {
                    $filter = $filters->addField($fieldName);
                }

                $field = $definition->getField($fieldName);
                $targetDefinition = $field->getTargetEntity();
                $targetClass = $field->getTargetClass();
                if (null !== $targetDefinition) {
                    $dataType = $this->getExactType($targetDefinition, $targetClass, $dataType);
                    if ($targetClass && ExtendHelper::isOutdatedEnumOptionEntity($targetClass)) {
                        $filter->setArrayAllowed();
                    }
                }

                if (!$filter->hasDataType()) {
                    $filter->setDataType($dataType);
                }
                $this->setFilterArrayAllowed($filter);
                $this->setFilterRangeAllowed($filter);
                if (
                    !$filter->hasType()
                    && !$filter->hasCollection()
                    && $this->isCollectionValuedAssociation($metadata, $propertyPath)
                ) {
                    $filter->setIsCollection(true);
                }
            }
        }
    }

    private function completeFiltersForExtendedAssociations(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ): void {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }
            $dataType = $field->getDataType();
            if (!DataType::isExtendedAssociation($dataType)) {
                continue;
            }

            $filter = $filters->getOrAddField($fieldName);
            if (!$filter->hasDataType()) {
                $filter->setDataType(DataType::INTEGER);
            }
            if (!$filter->hasType()) {
                $filter->setType('association');
            }
            $this->setFilterArrayAllowed($filter);
            $this->setFilterRangeAllowed($filter);
            $options = $filter->getOptions() ?? [];
            [$associationType, $associationKind] = DataType::parseExtendedAssociation($dataType);
            $options['associationOwnerClass'] = $metadata->name;
            $options['associationType'] = $associationType;
            if ($associationKind) {
                $options['associationKind'] = $associationKind;
            }
            $filter->setOptions($options);
        }
    }

    private function setFilterArrayAllowed(FilterFieldConfig $filter): void
    {
        if (!$filter->hasArrayAllowed()) {
            $dataType = $this->getFilterDataType($filter);
            if ($dataType && !isset($this->disallowArrayDataTypes[$dataType])) {
                $filter->setArrayAllowed();
            }
        }
    }

    private function setFilterRangeAllowed(FilterFieldConfig $filter): void
    {
        if (!$filter->hasRangeAllowed()) {
            $dataType = $this->getFilterDataType($filter);
            if ($dataType && !isset($this->disallowRangeDataTypes[$dataType])) {
                $filter->setRangeAllowed();
            }
        }
    }

    private function getFilterDataType(FilterFieldConfig $filter): ?string
    {
        $dataType = $filter->getDataType();
        if ($dataType) {
            $dataTypeDetailDelimiterPos = strpos($dataType, DataType::DETAIL_DELIMITER);
            if (false !== $dataTypeDetailDelimiterPos) {
                $dataType = substr($dataType, 0, $dataTypeDetailDelimiterPos);
            }
        }

        return $dataType;
    }

    private function getFieldDataType(ClassMetadata $metadata, string $propertyPath): ?string
    {
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        $lastFieldName = array_pop($path);
        if (!empty($path)) {
            $parentMetadata = $this->doctrineHelper->findEntityMetadataByPath($metadata->name, $path);
            if (null === $parentMetadata) {
                return null;
            }
            $metadata = $parentMetadata;
        }

        return $this->doctrineHelper->getFieldDataType($metadata, $lastFieldName);
    }

    private function getExactType(
        EntityDefinitionConfig $targetDefinition,
        ?string $targetClass,
        string $defaultDataType
    ): string {
        if ($targetClass && \count($targetDefinition->getIdentifierFieldNames()) === 1) {
            $identifierFieldName = $targetDefinition->getIdentifierFieldNames()[0];
            $idPropertyPath = $targetDefinition->getField($identifierFieldName)?->getPropertyPath();
            if ($idPropertyPath && $idPropertyPath !== $identifierFieldName) {
                if (ExtendHelper::isOutdatedEnumOptionEntity($targetClass)) {
                    $targetClass = EnumOption::class;
                }
                $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass);
                $defaultDataType = $targetMetadata?->getTypeOfField($idPropertyPath) ?? $defaultDataType;
            }
        }

        return $defaultDataType;
    }
}
