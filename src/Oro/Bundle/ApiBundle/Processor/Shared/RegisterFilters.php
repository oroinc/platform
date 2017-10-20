<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Filter\FieldAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\MetadataAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\RequestAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\Context;

abstract class RegisterFilters implements ProcessorInterface
{
    /** @var FilterFactoryInterface */
    protected $filterFactory;

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
            }
            if ($filter instanceof FieldAwareFilterInterface) {
                $filter->setField($propertyPath);
            }
            if ($filter instanceof RequestAwareFilterInterface) {
                $filter->setRequestType($context->getRequestType());
            }
            if ($filter instanceof MetadataAwareFilterInterface) {
                $filter->setMetadata($context->getMetadata());
            }
        }

        return $filter;
    }
}
