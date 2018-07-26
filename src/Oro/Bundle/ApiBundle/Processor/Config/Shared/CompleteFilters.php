<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityConfigInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Makes sure that the filters configuration contains all supported filters
 * and all filters are fully configured.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CompleteFilters extends CompleteSection
{
    /** @var array [data type => true, ...] */
    protected $disallowArrayDataTypes;

    /** @var array [data type => true, ...] */
    protected $disallowRangeDataTypes;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string[]       $disallowArrayDataTypes
     * @param string[]       $disallowRangeDataTypes
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        array $disallowArrayDataTypes,
        array $disallowRangeDataTypes
    ) {
        parent::__construct($doctrineHelper);
        $this->disallowArrayDataTypes = \array_fill_keys($disallowArrayDataTypes, true);
        $this->disallowRangeDataTypes = \array_fill_keys($disallowRangeDataTypes, true);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $this->complete($context->getFilters(), $context->getClassName(), $context->getResult());
    }

    /**
     * {@inheritdoc}
     */
    protected function completeFields(
        EntityConfigInterface $section,
        string $entityClass,
        EntityDefinitionConfig $definition
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        /** @var FiltersConfig $section */
        $this->completePreConfiguredFilters($section, $metadata, $definition);
        $this->completeFiltersForIdentifierFields($section, $metadata, $definition);
        $this->completeFiltersForFields($section, $metadata, $definition);
        $this->completeFiltersForAssociations($section, $metadata, $definition);
        $this->completeFiltersForExtendedAssociations($section, $metadata, $definition);
    }

    /**
     * @param FiltersConfig          $filters
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     */
    protected function completePreConfiguredFilters(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        $filtersFields = $filters->getFields();
        foreach ($filtersFields as $fieldName => $filter) {
            if ($filter->hasType()) {
                continue;
            }
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
            if (!$filter->hasCollection()
                && $propertyPath !== $fieldName
                && $this->isCollectionValuedAssociation($metadata, $propertyPath)
            ) {
                $filter->setIsCollection(true);
            }
            $this->setFilterArrayAllowed($filter);
            $this->setFilterRangeAllowed($filter);
        }
    }

    /**
     * @param FiltersConfig          $filters
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     */
    protected function completeFiltersForIdentifierFields(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        if (\is_subclass_of($metadata->name, AbstractEnumValue::class)) {
            $enumIdFieldName = $this->getEnumIdentifierFieldName($definition);
            if (null !== $enumIdFieldName) {
                $enumIdFilter = $filters->getOrAddField($enumIdFieldName);
                if (!$enumIdFilter->hasArrayAllowed()) {
                    $enumIdFilter->setArrayAllowed();
                }
            }
        }

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

    /**
     * @param FiltersConfig          $filters
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     */
    protected function completeFiltersForFields(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        $indexedFields = $this->doctrineHelper->getIndexedFields($metadata);
        foreach ($indexedFields as $propertyPath => $dataType) {
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
     * @param FiltersConfig          $filters
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function completeFiltersForAssociations(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        $indexedAssociations = $this->doctrineHelper->getIndexedAssociations($metadata);
        foreach ($indexedAssociations as $propertyPath => $dataType) {
            $filter = $filters->findField($propertyPath, true);
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName && (null !== $filter || !$filters->hasField($fieldName))) {
                if (null === $filter) {
                    $filter = $filters->addField($fieldName);
                }

                $field = $definition->getField($fieldName);
                $targetDefinition = $field->getTargetEntity();
                if (null !== $targetDefinition) {
                    $targetClass = $field->getTargetClass();
                    if ($targetClass && \is_subclass_of($targetClass, AbstractEnumValue::class)) {
                        $enumIdFieldName = $this->getEnumIdentifierFieldName($targetDefinition);
                        if (null !== $enumIdFieldName && !$filter->hasArrayAllowed()) {
                            $filter->setArrayAllowed();
                        }
                    }
                }

                if (!$filter->hasDataType()) {
                    $filter->setDataType($dataType);
                }
                $this->setFilterArrayAllowed($filter);
                $this->setFilterRangeAllowed($filter);
                if (!$filter->hasType()
                    && !$filter->hasCollection()
                    && $metadata->isCollectionValuedAssociation($propertyPath)
                ) {
                    $filter->setIsCollection(true);
                }
            }
        }
    }

    /**
     * @param FiltersConfig          $filters
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     */
    protected function completeFiltersForExtendedAssociations(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
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
            $options = $filter->getOptions();
            if (null === $options) {
                $options = [];
            }
            list($associationType, $associationKind) = DataType::parseExtendedAssociation($dataType);
            $options = \array_replace($options, [
                'associationOwnerClass' => $metadata->name,
                'associationType'       => $associationType,
                'associationKind'       => $associationKind
            ]);
            $filter->setOptions($options);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     *
     * @return string|null
     */
    protected function getEnumIdentifierFieldName(EntityDefinitionConfig $definition)
    {
        $idFieldNames = $definition->getIdentifierFieldNames();
        if (count($idFieldNames) !== 1) {
            return null;
        }

        $idFieldName = $idFieldNames[0];
        $idField = $definition->getField($idFieldName);
        if (null === $idField || $idField->getPropertyPath($idFieldName) !== 'id') {
            return null;
        }

        return $idFieldName;
    }

    /**
     * @param FilterFieldConfig $filter
     */
    protected function setFilterArrayAllowed(FilterFieldConfig $filter)
    {
        if (!$filter->hasArrayAllowed()) {
            $dataType = $filter->getDataType();
            if ($dataType && !isset($this->disallowArrayDataTypes[$dataType])) {
                $filter->setArrayAllowed();
            }
        }
    }

    /**
     * @param FilterFieldConfig $filter
     */
    protected function setFilterRangeAllowed(FilterFieldConfig $filter)
    {
        if (!$filter->hasRangeAllowed()) {
            $dataType = $filter->getDataType();
            if ($dataType && !isset($this->disallowRangeDataTypes[$dataType])) {
                $filter->setRangeAllowed();
            }
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $propertyPath
     *
     * @return string|null
     */
    protected function getFieldDataType(ClassMetadata $metadata, string $propertyPath): ?string
    {
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        $lastFieldName = \array_pop($path);
        if (!empty($path)) {
            $parentMetadata = $this->doctrineHelper->findEntityMetadataByPath($metadata->name, $path);
            if (null === $parentMetadata) {
                return null;
            }
            $metadata = $parentMetadata;
        }

        return $this->doctrineHelper->getFieldDataType($metadata, $lastFieldName);
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $propertyPath
     *
     * @return bool
     */
    protected function isCollectionValuedAssociation(ClassMetadata $metadata, string $propertyPath): bool
    {
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        $lastFieldName = \array_pop($path);
        if (!empty($path)) {
            $parentMetadata = $this->doctrineHelper->findEntityMetadataByPath($metadata->name, $path);
            if (null === $parentMetadata) {
                return false;
            }
            $metadata = $parentMetadata;
        }

        return
            $metadata->hasAssociation($lastFieldName)
            && $metadata->isCollectionValuedAssociation($lastFieldName);
    }
}
