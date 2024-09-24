<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FieldAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
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
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
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

        /** @var EntityDefinitionConfig $config */
        $config = $context->getConfig();
        $idFieldName = $this->getSingleIdentifierFieldName($config);
        $associationNames = $this->getAssociationNames($metadata);
        $filterCollection = $context->getFilters();
        $fields = $configOfFilters->getFields();
        foreach ($fields as $filterKey => $field) {
            if ($filterCollection->has($filterKey)) {
                continue;
            }
            $propertyPath = $field->getPropertyPath($filterKey);
            try {
                $filter = $this->createFilter($field, $propertyPath, $context);
            } catch (\Throwable $e) {
                throw new \LogicException(
                    sprintf('The filter "%s" for "%s" cannot be created.', $filterKey, $context->getClassName()),
                    $e->getCode(),
                    $e
                );
            }
            if (null !== $filter) {
                if ($filter instanceof FieldAwareFilterInterface) {
                    if ($idFieldName && $filterKey === $idFieldName) {
                        $this->updateSingleIdentifierOperators($filter);
                    }
                    if (\in_array($propertyPath, $associationNames, true)) {
                        $this->updateAssociationOperators($filter, $field->isCollection());
                    }
                    $fieldConfig = $config->getField($propertyPath);
                    if (null !== $fieldConfig) {
                        $targetEntityClass = $fieldConfig->getTargetClass();
                        if ($targetEntityClass) {
                            $targetEntityConfig = $fieldConfig->getTargetEntity();
                            if (null !== $targetEntityConfig) {
                                $this->updateAssociationFieldPropertyPath(
                                    $filter,
                                    $targetEntityClass,
                                    $targetEntityConfig
                                );
                            }
                        }
                    }
                }
                $filterCollection->add($filterKey, $filter);
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
        FieldAwareFilterInterface $filter,
        string $targetEntityClass,
        EntityDefinitionConfig $targetEntityConfig
    ): void {
        $idFieldName = $this->getSingleIdentifierFieldName($targetEntityConfig);
        if (!$idFieldName) {
            return;
        }

        $idFieldPropertyPath = $targetEntityConfig->getField($idFieldName)?->getPropertyPath();
        if (!$idFieldPropertyPath || $idFieldPropertyPath === $idFieldName) {
            return;
        }

        $idFieldPropertyPaths = $this->doctrineHelper->getEntityIdentifierFieldNames($targetEntityClass, false);
        if (\count($idFieldPropertyPaths) === 1 && $idFieldPropertyPath === $idFieldPropertyPaths[0]) {
            return;
        }

        $filter->setField($filter->getField() . ConfigUtil::PATH_DELIMITER . $idFieldPropertyPath);
    }
}
