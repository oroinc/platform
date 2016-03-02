<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Context;

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
                    $context->getConfigOfSorters()
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
     * @param array|null         $orderBy
     * @param SortersConfig|null $sorters
     */
    protected function validateSortValue($orderBy, SortersConfig $sorters = null)
    {
        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $direction) {
                if (null === $sorters
                    || !$sorters->hasField($field)
                    || $sorters->getField($field)->isExcluded()
                ) {
                    throw new NotAcceptableHttpException(
                        sprintf('Sorting by "%s" is not supported.', $field)
                    );
                }
            }
        }
    }
}
