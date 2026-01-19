<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultOrdering;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Filter\SearchQueryFilter;
use Oro\Bundle\SearchBundle\Api\Filter\SimpleSearchFilter;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets default sorting filter when data are filtered
 * by "searchText" or "searchQuery" filter or data aggregation is requested.
 */
class SetDefaultSearchTextSorting implements ProcessorInterface
{
    public function __construct(
        private readonly FilterNamesRegistry $filterNamesRegistry
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->isProcessed(SetDefaultOrdering::OPERATION_NAME)) {
            return;
        }

        if (!$context->getConfig()?->isSortingEnabled()) {
            return;
        }

        if ($this->hasSearchFilter($context->getFilters(), $context->getFilterValues())) {
            $sortFilterName = $this->filterNamesRegistry
                ->getFilterNames($context->getRequestType())
                ->getSortFilterName();
            /** @var SortFilter|null $sortFilter */
            $sortFilter = $context->getFilters()->get($sortFilterName);
            $sortFilter?->setDefaultValue(function () {
                return null;
            });
        }
        $context->setProcessed(SetDefaultOrdering::OPERATION_NAME);
    }

    private function hasSearchFilter(FilterCollection $filters, FilterValueAccessorInterface $filterValues): bool
    {
        foreach ($filterValues->getAll() as $filterKey => $values) {
            if ($this->isSearchFilter($filters->get($filterKey))) {
                return true;
            }
        }

        return false;
    }

    private function isSearchFilter(?FilterInterface $filter): bool
    {
        return
            $filter instanceof SimpleSearchFilter
            || $filter instanceof SearchQueryFilter
            || $filter instanceof SearchAggregationFilter;
    }
}
