<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Filter\CollectionAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FieldAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
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
        ComparisonFilter::CONTAINS,
        ComparisonFilter::NOT_CONTAINS
    ];

    /** @var FilterFactoryInterface */
    private $filterFactory;

    /**
     * @param FilterFactoryInterface $filterFactory
     */
    public function __construct(FilterFactoryInterface $filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }

    /**
     * @param FilterFieldConfig $filterConfig
     * @param string            $propertyPath
     * @param Context           $context
     *
     * @return StandaloneFilter|null
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function createFilter(FilterFieldConfig $filterConfig, $propertyPath, Context $context)
    {
        $filterOptions = $filterConfig->getOptions();
        if (null === $filterOptions) {
            $filterOptions = [];
        }
        $filterType = $filterConfig->getType();
        $dataType = $filterConfig->getDataType();
        if (!$filterType) {
            $filterType = $dataType;
        } elseif ($filterType !== $dataType) {
            $filterOptions[FilterFactoryInterface::DATA_TYPE_OPTION] = $dataType;
        }
        $filter = $this->filterFactory->createFilter($filterType, $filterOptions);
        if (null !== $filter) {
            $filter->setArrayAllowed($filterConfig->isArrayAllowed());
            $filter->setRangeAllowed($filterConfig->isRangeAllowed());
            $filter->setDescription($filterConfig->getDescription());
            $operators = $filterConfig->getOperators();
            if (!empty($operators)) {
                $filter->setSupportedOperators($operators);
            } elseif (!$filterConfig->hasType() && $filterConfig->isCollection()) {
                $filter->setSupportedOperators(
                    \array_merge($filter->getSupportedOperators(), self::COLLECTION_ASSOCIATION_ADDITIONAL_OPERATORS)
                );
            }
            if ($filter instanceof FieldAwareFilterInterface) {
                $filter->setField($propertyPath);
            }
            if ($filterConfig->isCollection()) {
                if ($filter instanceof CollectionAwareFilterInterface) {
                    $filter->setCollection(true);
                } else {
                    throw new \LogicException(\sprintf(
                        'The filter by "%s" does not support the "collection" option',
                        $propertyPath
                    ));
                }
            }
            if ($filter instanceof RequestAwareFilterInterface) {
                $filter->setRequestType($context->getRequestType());
            }
            if ($filter instanceof MetadataAwareFilterInterface) {
                $metadata = $context->getMetadata();
                if (null === $metadata) {
                    throw new \LogicException(\sprintf(
                        'The metadata for class "%s" does not exist, but it required for the filter by "%s"',
                        $context->getClassName(),
                        $propertyPath
                    ));
                }
                $filter->setMetadata($metadata);
            }
        }

        return $filter;
    }
}
