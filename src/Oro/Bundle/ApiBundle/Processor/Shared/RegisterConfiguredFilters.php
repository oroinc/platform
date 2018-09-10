<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FieldAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Registers filters according to the "filters" configuration section.
 */
class RegisterConfiguredFilters extends RegisterFilters
{
    private const ASSOCIATION_ALLOWED_OPERATORS            = [
        ComparisonFilter::EQ,
        ComparisonFilter::NEQ,
        ComparisonFilter::EXISTS,
        ComparisonFilter::NEQ_OR_NULL
    ];
    private const COLLECTION_ASSOCIATION_ALLOWED_OPERATORS = [
        ComparisonFilter::EQ,
        ComparisonFilter::NEQ,
        ComparisonFilter::EXISTS,
        ComparisonFilter::NEQ_OR_NULL,
        ComparisonFilter::CONTAINS,
        ComparisonFilter::NOT_CONTAINS
    ];
    private const SINGLE_IDENTIFIER_EXCLUDED_OPERATORS     = [
        ComparisonFilter::EXISTS,
        ComparisonFilter::NEQ_OR_NULL
    ];

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param FilterFactoryInterface $filterFactory
     * @param DoctrineHelper         $doctrineHelper
     */
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
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $configOfFilters = $context->getConfigOfFilters();
        if (null === $configOfFilters || $configOfFilters->isEmpty()) {
            // a filters' configuration does not contains any data
            return;
        }

        $metadata = null;
        $entityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getClassName(),
            $context->getConfig()
        );
        if ($entityClass) {
            // only manageable entities or resources based on manageable entities can have the metadata
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        }

        $idFieldName = $this->getSingleIdentifierFieldName($context->getConfig());
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
                }

                $filters->add($filterKey, $filter);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig|null $config
     *
     * @return string|null
     */
    private function getSingleIdentifierFieldName(EntityDefinitionConfig $config = null)
    {
        if (null === $config) {
            return null;
        }
        $idFieldNames = $config->getIdentifierFieldNames();
        if (\count($idFieldNames) !== 1) {
            return null;
        }

        return \reset($idFieldNames);
    }

    /**
     * @param ClassMetadata|null $metadata
     *
     * @return string[]
     */
    private function getAssociationNames(?ClassMetadata $metadata)
    {
        return null !== $metadata
            ? \array_keys($this->doctrineHelper->getIndexedAssociations($metadata))
            : [];
    }

    /**
     * @param StandaloneFilter $filter
     */
    private function updateSingleIdentifierOperators(StandaloneFilter $filter)
    {
        $filter->setSupportedOperators(
            \array_diff($filter->getSupportedOperators(), self::SINGLE_IDENTIFIER_EXCLUDED_OPERATORS)
        );
    }

    /**
     * @param StandaloneFilter $filter
     * @param bool             $isCollection
     */
    private function updateAssociationOperators(StandaloneFilter $filter, bool $isCollection)
    {
        $allowedOperators = $isCollection
            ? self::COLLECTION_ASSOCIATION_ALLOWED_OPERATORS
            : self::ASSOCIATION_ALLOWED_OPERATORS;
        if ([] !== \array_diff($filter->getSupportedOperators(), $allowedOperators)) {
            $filter->setSupportedOperators($allowedOperators);
        }
    }
}
