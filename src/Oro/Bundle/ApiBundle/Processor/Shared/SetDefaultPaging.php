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

    /**
     * {@inheritdoc}
     */
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
        $filters = $context->getFilters();
        $this->addPageSizeFilter($filterNames->getPageSizeFilterName(), $filters, $pageSize);
        $this->addPageNumberFilter($filterNames->getPageNumberFilterName(), $filters);
    }

    protected function addPageNumberFilter(string $filterName, FilterCollection $filters): void
    {
        /**
         * "page number" filter must be added after "page size" filter because it depends on this filter
         * @see \Oro\Bundle\ApiBundle\Filter\PageNumberFilter::apply
         */
        $pageNumberFilter = $filters->get($filterName);
        if (null === $pageNumberFilter) {
            $pageNumberFilter = new PageNumberFilter(
                DataType::UNSIGNED_INTEGER,
                'The page number, starting from 1.',
                1
            );
        } else {
            // remove "page number" filter to make sure that it is added after "page size" filter
            $filters->remove($filterName);
        }
        $filters->add($filterName, $pageNumberFilter, false);
    }

    protected function addPageSizeFilter(string $filterName, FilterCollection $filters, ?int $pageSize): void
    {
        if (!$filters->has($filterName)) {
            $filters->add(
                $filterName,
                new PageSizeFilter(
                    DataType::INTEGER,
                    'The number of items per page.',
                    $pageSize ?? $this->defaultPageSize
                ),
                false
            );
        }
    }
}
