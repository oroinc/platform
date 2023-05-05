<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Filter\FieldAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Registers filters according to the "filters" configuration section.
 */
class RegisterConfiguredFilters extends RegisterFilters
{
    private const ASSOCIATION_ALLOWED_OPERATORS = [
        FilterOperator::EQ,
        FilterOperator::NEQ,
        FilterOperator::EXISTS,
        FilterOperator::NEQ_OR_NULL
    ];
    private const COLLECTION_ASSOCIATION_ALLOWED_OPERATORS = [
        FilterOperator::EQ,
        FilterOperator::NEQ,
        FilterOperator::EXISTS,
        FilterOperator::NEQ_OR_NULL,
        FilterOperator::CONTAINS,
        FilterOperator::NOT_CONTAINS
    ];
    private const SINGLE_IDENTIFIER_EXCLUDED_OPERATORS = [
        FilterOperator::EXISTS,
        FilterOperator::NEQ_OR_NULL
    ];

    private DoctrineHelper $doctrineHelper;

    public function __construct(
        FilterFactoryInterface $filterFactory,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($filterFactory);
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $configOfFilters = $context->getConfigOfFilters();
        if (null === $configOfFilters || $configOfFilters->isEmpty()) {
            // a filters' configuration does not contains any data
            return;
        }

        $metadata = null;
        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
        if ($entityClass) {
            // only manageable entities or resources based on manageable entities can have the metadata
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        }

        $configs = $context->getConfig();
        $idFieldName = $this->getSingleIdentifierFieldName($configs);
        $associationNames = $this->getAssociationNames($metadata);
        $filters = $context->getFilters();
        $fields = $configOfFilters->getFields();
        foreach ($fields as $filterKey => $field) {
            if ($filters->has($filterKey)) {
                continue;
            }
            $propertyPath = $field->getPropertyPath($filterKey);
            $filter = $this->createFilter($field, $propertyPath, $context);
            if (null !== $filter) {
                if ($filter instanceof FieldAwareFilterInterface) {
                    if ($idFieldName && $filterKey === $idFieldName) {
                        $this->updateSingleIdentifierOperators($filter);
                    }
                    if (\in_array($propertyPath, $associationNames, true)) {
                        $this->updateAssociationOperators($filter, $field->isCollection());
                    }
                    if ($configs->hasField($propertyPath) && $configs->getField($propertyPath)?->getTargetEntity()) {
                        $this->updateAssociationFieldPropertyPath($filter, $configs->getField($propertyPath));
                    }
                }

                $filters->add($filterKey, $filter);
            }
        }
    }

    private function getSingleIdentifierFieldName(?EntityDefinitionConfig $config = null): ?string
    {
        if (null === $config) {
            return null;
        }
        $idFieldNames = $config->getIdentifierFieldNames();
        if (\count($idFieldNames) !== 1) {
            return null;
        }

        return reset($idFieldNames);
    }

    /**
     * @param ClassMetadata|null $metadata
     *
     * @return string[]
     */
    private function getAssociationNames(?ClassMetadata $metadata): array
    {
        return null !== $metadata
            ? array_keys($this->doctrineHelper->getIndexedAssociations($metadata))
            : [];
    }

    private function updateSingleIdentifierOperators(StandaloneFilter $filter): void
    {
        $filter->setSupportedOperators(
            array_diff($filter->getSupportedOperators(), self::SINGLE_IDENTIFIER_EXCLUDED_OPERATORS)
        );
    }

    private function updateAssociationOperators(StandaloneFilter $filter, bool $isCollection): void
    {
        $allowedOperators = $isCollection
            ? self::COLLECTION_ASSOCIATION_ALLOWED_OPERATORS
            : self::ASSOCIATION_ALLOWED_OPERATORS;
        if ([] !== array_diff($filter->getSupportedOperators(), $allowedOperators)) {
            $filter->setSupportedOperators($allowedOperators);
        }
    }

    private function updateAssociationFieldPropertyPath(
        FieldAwareFilterInterface|StandaloneFilter $filter,
        ?EntityDefinitionFieldConfig $config
    ): void {
        if (null !== $config) {
            $path = $filter->getField();
            $singleIdName = $this->getSingleIdentifierFieldName($config->getTargetEntity());
            if ($singleIdName) {
                $targetEntityConfig = $config->getTargetEntity();
                $pathProperty = $targetEntityConfig?->getField($singleIdName)?->getPropertyPath();
                if ($pathProperty && $pathProperty !== $singleIdName) {
                    $filter->setField(sprintf('%s.%s', $path, $pathProperty));
                }
            }
        }
    }
}
