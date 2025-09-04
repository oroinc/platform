<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Filter\SearchQueryFilter;
use Oro\Bundle\SearchBundle\Api\Filter\SimpleSearchFilter;
use Oro\Bundle\SearchBundle\Api\SearchMappingProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds allowed fields to the "sort" filter description
 * for the case when data are loaded via the search index.
 */
class UpdateSortFilterDescription implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly SearchMappingProvider $searchMappingProvider,
        private readonly FilterNamesRegistry $filterNamesRegistry
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        if (!$this->hasSearchFilter($context->getFilters())) {
            return;
        }

        $sortFilterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getSortFilterName();
        /** @var SortFilter|null $sortFilter */
        $sortFilter = $context->getFilters()->get($sortFilterName);
        if (null === $sortFilter) {
            return;
        }

        $sortableFieldNames = array_keys($this->searchMappingProvider->getSearchFieldTypes(
            $context->getManageableEntityClass($this->doctrineHelper)
        ));
        if (!$sortableFieldNames) {
            return;
        }

        sort($sortableFieldNames);
        $sortFilter->setDescription(
            $sortFilter->getDescription()
            . ' Allowed fields when a search filter is used: ' . implode(', ', $sortableFieldNames) . '.'
        );
    }

    private function hasSearchFilter(FilterCollection $filters): bool
    {
        foreach ($filters as $filter) {
            if ($this->isSearchFilter($filter)) {
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
