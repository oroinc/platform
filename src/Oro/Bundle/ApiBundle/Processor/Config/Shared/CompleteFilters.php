<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityConfigInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\DataType;
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
        $this->disallowArrayDataTypes = array_fill_keys($disallowArrayDataTypes, true);
        $this->disallowRangeDataTypes = array_fill_keys($disallowRangeDataTypes, true);
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
        $entityClass,
        EntityDefinitionConfig $definition
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        /** @var FiltersConfig $section */
        $this->completePreConfiguredFieldFilters($section, $metadata, $definition);
        $this->completeIdentifierFieldFilters($section, $metadata, $definition);
        $this->completeIndexedFieldFilters($section, $metadata, $definition);
        $this->completeAssociationFilters($section, $metadata, $definition);
        $this->completeExtendedAssociationFilters($section, $metadata, $definition);
    }

    /**
     * @param FiltersConfig          $filters
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     */
    protected function completePreConfiguredFieldFilters(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        $filtersFields = $filters->getFields();
        foreach ($filtersFields as $fieldName => $filter) {
            $propertyPath = $filter->getPropertyPath();
            $field = $definition->getField($fieldName);
            if (null !== $field) {
                $propertyPath = $field->getPropertyPath();
                if (!$filter->hasDataType()) {
                    $dataType = $field->getDataType();
                    if ($dataType) {
                        $filter->setDataType($dataType);
                    }
                }
            }
            if (!$propertyPath) {
                $propertyPath = $fieldName;
            }

            if (!$filter->hasDataType() && $metadata->hasField($propertyPath)) {
                $filter->setDataType($metadata->getTypeOfField($propertyPath));
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
    protected function completeIdentifierFieldFilters(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        if (is_subclass_of($metadata->name, AbstractEnumValue::class)) {
            $this->completeEnumIdentifierFilter($definition, $filters);
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
    protected function completeIndexedFieldFilters(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        $indexedFields = $this->doctrineHelper->getIndexedFields($metadata);
        foreach ($indexedFields as $propertyPath => $dataType) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName) {
                $filter = $filters->getOrAddField($fieldName);
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
     */
    protected function completeAssociationFilters(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        $relations = $this->doctrineHelper->getIndexedAssociations($metadata);
        foreach ($relations as $propertyPath => $dataType) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName) {
                $field = $definition->getField($fieldName);
                $targetDefinition = $field->getTargetEntity();
                if (null !== $targetDefinition) {
                    $targetClass = $field->getTargetClass();
                    if ($targetClass && is_subclass_of($targetClass, AbstractEnumValue::class)) {
                        $this->completeEnumIdentifierFilter($targetDefinition, $filters, $fieldName);
                    }
                }

                $filter = $filters->getOrAddField($fieldName);
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
     */
    protected function completeExtendedAssociationFilters(
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
            $options = array_replace($options, [
                'associationOwnerClass' => $metadata->name,
                'associationType'       => $associationType,
                'associationKind'       => $associationKind
            ]);
            $filter->setOptions($options);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param FiltersConfig          $filters
     * @param string|null            $filterName
     */
    protected function completeEnumIdentifierFilter(
        EntityDefinitionConfig $definition,
        FiltersConfig $filters,
        $filterName = null
    ) {
        $idFieldNames = $definition->getIdentifierFieldNames();
        if (count($idFieldNames) !== 1) {
            return;
        }

        $idFieldName = $idFieldNames[0];
        $idField = $definition->getField($idFieldName);
        if (null === $idField || $idField->getPropertyPath($idFieldName) !== 'id') {
            return;
        }

        if (null === $filterName) {
            $filterName = $idFieldName;
        }
        $filter = $filters->getOrAddField($filterName);
        if (!$filter->hasArrayAllowed()) {
            $filter->setArrayAllowed();
        }
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
}
