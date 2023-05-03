<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Filter\CollectionAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\ConfigAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FieldAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\MetadataAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\RequestAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Abstract class for register filters processor.
 */
abstract class RegisterFilters implements ProcessorInterface
{
    private const COLLECTION_ASSOCIATION_ADDITIONAL_OPERATORS = [
        FilterOperator::CONTAINS,
        FilterOperator::NOT_CONTAINS
    ];

    private FilterFactoryInterface $filterFactory;

    public function __construct(FilterFactoryInterface $filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function createFilter(
        FilterFieldConfig $filterConfig,
        string $propertyPath,
        Context $context
    ): ?StandaloneFilter {
        $filterOptions = $this->getFilterOptions($filterConfig);
        $filterType = $filterConfig->getType();
        $dataType = $filterConfig->getDataType();
        if (!$filterType) {
            $filterType = $dataType;
        } elseif ($filterType !== $dataType) {
            $filterOptions[FilterFactoryInterface::DATA_TYPE_OPTION] = $dataType;
        }
        $filter = $this->filterFactory->createFilter($filterType, $filterOptions);
        if (null !== $filter) {
            $this->initializeFilterOptions($filter, $filterConfig);
            if ($filter instanceof FieldAwareFilterInterface) {
                $filter->setField($propertyPath);
            }
            if ($filterConfig->isCollection()) {
                if (!$filter instanceof CollectionAwareFilterInterface) {
                    throw new \LogicException(sprintf(
                        'The filter by "%s" does not support the "collection" option.',
                        $propertyPath
                    ));
                }
                $filter->setCollection(true);
            }
            if ($filter instanceof RequestAwareFilterInterface) {
                $filter->setRequestType($context->getRequestType());
            }
            if ($filter instanceof ConfigAwareFilterInterface) {
                $config = $context->getConfig();
                if (null === $config) {
                    throw new \LogicException(sprintf(
                        'The config for class "%s" does not exist, but it required for the filter by "%s".',
                        $context->getClassName(),
                        $propertyPath
                    ));
                }
                $filter->setConfig($config);
            }
            if ($filter instanceof MetadataAwareFilterInterface) {
                $metadata = $context->getMetadata();
                if (null === $metadata) {
                    throw new \LogicException(sprintf(
                        'The metadata for class "%s" does not exist, but it required for the filter by "%s".',
                        $context->getClassName(),
                        $propertyPath
                    ));
                }

                if ($filter instanceof FieldAwareFilterInterface && $metadata->hasAssociation($filter->getField())) {
                    $metadata = $metadata->getAssociation($filter->getField())?->getTargetMetadata() ?? $metadata;
                }
                $filter->setMetadata($metadata);
            }
        }

        return $filter;
    }

    private function getFilterOptions(FilterFieldConfig $filterConfig): array
    {
        $filterOptions = $filterConfig->getOptions();
        if (null === $filterOptions) {
            $filterOptions = [];
        }

        return $filterOptions;
    }

    private function initializeFilterOptions(StandaloneFilter $filter, FilterFieldConfig $filterConfig): void
    {
        if ($filterConfig->hasArrayAllowed()) {
            $filter->setArrayAllowed($filterConfig->isArrayAllowed());
        }
        if ($filterConfig->hasRangeAllowed()) {
            $filter->setRangeAllowed($filterConfig->isRangeAllowed());
        }
        if ($filterConfig->hasDescription()) {
            $filter->setDescription($filterConfig->getDescription());
        }
        $operators = $filterConfig->getOperators();
        if (!empty($operators)) {
            $filter->setSupportedOperators($operators);
        } elseif (!$filterConfig->hasType() && $filterConfig->isCollection()) {
            $filter->setSupportedOperators(
                array_merge($filter->getSupportedOperators(), self::COLLECTION_ASSOCIATION_ADDITIONAL_OPERATORS)
            );
        }
    }
}
