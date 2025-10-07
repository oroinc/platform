<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets default paging filters.
 */
class SetDefaultPaging implements ProcessorInterface
{
    private FilterNamesRegistry $filterNamesRegistry;
    private int $defaultPageSize;

    public function __construct(FilterNamesRegistry $filterNamesRegistry, int $defaultPageSize)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
        $this->defaultPageSize = $defaultPageSize;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $pageSize = $context->getConfig()?->getPageSize();
        if (-1 === $pageSize) {
            // the paging is disabled
            return;
        }

        $filterNames = $this->filterNamesRegistry->getFilterNames($context->getRequestType());
        $filterCollection = $context->getFilters();
        $this->addPageSizeFilter($filterNames->getPageSizeFilterName(), $filterCollection, $pageSize);
        $this->addPageNumberFilter($filterNames->getPageNumberFilterName(), $filterCollection);
    }

    private function addPageNumberFilter(string $filterName, FilterCollection $filterCollection): void
    {
        /**
         * "page number" filter must be added after "page size" filter because it depends on this filter
         * @see \Oro\Bundle\ApiBundle\Filter\PageNumberFilter::apply
         */
        $pageNumberFilter = $filterCollection->get($filterName);
        if (null === $pageNumberFilter) {
            $pageNumberFilter = new PageNumberFilter(
                DataType::UNSIGNED_INTEGER,
                'The page number, starting from 1.',
                1
            );
        } else {
            // remove "page number" filter to make sure that it is added after "page size" filter
            $filterCollection->remove($filterName);
        }
        $filterCollection->add($filterName, $pageNumberFilter, false);
    }

    private function addPageSizeFilter(string $filterName, FilterCollection $filterCollection, ?int $pageSize): void
    {
        if (!$filterCollection->has($filterName)) {
            $filterCollection->add(
                $filterName,
                new PageSizeFilter(
                    DataType::INTEGER,
                    'The number of records per page.',
                    $pageSize ?? $this->defaultPageSize
                ),
                false
            );
        }
    }
}
