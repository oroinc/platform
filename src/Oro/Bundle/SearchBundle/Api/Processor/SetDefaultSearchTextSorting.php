<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultOrdering;
use Oro\Bundle\SearchBundle\Api\Filter\SimpleSearchFilter;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets default sorting filter when data are filtered by "searchText" filter.
 */
class SetDefaultSearchTextSorting implements ProcessorInterface
{
    private FilterNamesRegistry $filterNamesRegistry;

    public function __construct(FilterNamesRegistry $filterNamesRegistry)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $config = $context->getConfig();
        if (null !== $config
            && $config->isSortingEnabled()
            && $this->hasSearchTextFilter($context->getFilters(), $context->getFilterValues())
        ) {
            $sortFilterName = $this->filterNamesRegistry
                ->getFilterNames($context->getRequestType())
                ->getSortFilterName();
            /** @var SortFilter $sortFilter */
            $sortFilter = $context->getFilters()->get($sortFilterName);
            $sortFilter->setDefaultValue(function () {
                return null;
            });
            $context->setProcessed(SetDefaultOrdering::OPERATION_NAME);
        }
    }

    private function hasSearchTextFilter(FilterCollection $filters, FilterValueAccessorInterface $filterValues): bool
    {
        /** @var FilterInterface $filter */
        foreach ($filters as $filterKey => $filter) {
            if ($filter instanceof SimpleSearchFilter && $filterValues->has($filterKey)) {
                return true;
            }
        }

        return false;
    }
}
