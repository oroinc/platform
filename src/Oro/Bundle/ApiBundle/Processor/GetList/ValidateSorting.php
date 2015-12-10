<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Validates that requested sorting is supported.
 */
class ValidateSorting implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $filterValues = $context->getFilterValues();
        $filters      = $context->getFilters();
        foreach ($filters as $filterKey => $filter) {
            if ($filter instanceof SortFilter) {
                $this->validateSortValue(
                    $this->getSortFilterValue($filterValues, $filterKey),
                    $context->hasConfigOfSorters() ? $context->getConfigOfSorters() : null
                );
            }
        }
    }

    /**
     * @param FilterValueAccessorInterface $filterValues
     * @param string                       $filterKey
     *
     * @return array|null
     */
    protected function getSortFilterValue(FilterValueAccessorInterface $filterValues, $filterKey)
    {
        $filterValue = null;
        if ($filterValues->has($filterKey)) {
            $filterValue = $filterValues->get($filterKey);
            if (null !== $filterValue) {
                $filterValue = $filterValue->getValue();
                if (empty($filterValue)) {
                    $filterValue = null;
                }
            }
        }

        return $filterValue;
    }

    /**
     * @param array|null $orderBy
     * @param array|null $sorters
     */
    protected function validateSortValue($orderBy, $sorters)
    {
        if (!empty($orderBy)) {
            $sortFields = !empty($sorters) && !empty($sorters[ConfigUtil::FIELDS])
                ? $sorters[ConfigUtil::FIELDS]
                : [];
            foreach ($orderBy as $field => $direction) {
                if (!array_key_exists($field, $sortFields)) {
                    throw new NotAcceptableHttpException(
                        sprintf('Sorting by "%s" is not supported.', $field)
                    );
                }
            }
        }
    }
}
