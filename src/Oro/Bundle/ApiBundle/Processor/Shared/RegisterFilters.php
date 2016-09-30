<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Filter\FieldAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;

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
     *
     * @return StandaloneFilter|null
     */
    protected function createFilter(FilterFieldConfig $filterConfig, $propertyPath)
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
            $filter->setDescription($filterConfig->getDescription());
            $operators = $filterConfig->getOperators();
            if (!empty($operators)) {
                $filter->setSupportedOperators($operators);
            }
            if ($filter instanceof FieldAwareFilterInterface) {
                $filter->setField($propertyPath);
            }
        }

        return $filter;
    }
}
