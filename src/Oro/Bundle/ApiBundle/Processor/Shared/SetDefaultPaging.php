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
 * Sets default paging for different kind of requests.
 * The default page number is 1, the default page size is 10.
 */
class SetDefaultPaging implements ProcessorInterface
{
    private const DEFAULT_PAGE_SIZE = 10;

    /** @var FilterNamesRegistry */
    private $filterNamesRegistry;

    /**
     * @param FilterNamesRegistry $filterNamesRegistry
     */
    public function __construct(FilterNamesRegistry $filterNamesRegistry)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

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

        $pageSize = null;
        $config = $context->getConfig();
        if (null !== $config) {
            $pageSize = $config->getPageSize();
        }
        if (-1 === $pageSize) {
            // the paging is disabled
            return;
        }

        $filterNames = $this->filterNamesRegistry->getFilterNames($context->getRequestType());
        $filters = $context->getFilters();
        $this->addPageSizeFilter($filterNames->getPageSizeFilterName(), $filters, $pageSize);
        $this->addPageNumberFilter($filterNames->getPageNumberFilterName(), $filters);
    }

    /**
     * @param string           $filterName
     * @param FilterCollection $filters
     */
    protected function addPageNumberFilter(string $filterName, FilterCollection $filters)
    {
        /**
         * "page number" filter must be added after "page size" filter because it depends on this filter
         * @see \Oro\Bundle\ApiBundle\Filter\PageNumberFilter::apply
         */
        if (!$filters->has($filterName)) {
            $filters->add(
                $filterName,
                new PageNumberFilter(
                    DataType::UNSIGNED_INTEGER,
                    'The page number, starting from 1.',
                    1
                )
            );
        } else {
            // make sure that "page number" filter is added after "page size" filter
            $pageFilter = $filters->get($filterName);
            $filters->remove($filterName);
            $filters->add($filterName, $pageFilter);
        }
    }

    /**
     * @param string           $filterName
     * @param FilterCollection $filters
     * @param int|null         $pageSize
     */
    protected function addPageSizeFilter(string $filterName, FilterCollection $filters, $pageSize)
    {
        if (!$filters->has($filterName)) {
            $filters->add(
                $filterName,
                new PageSizeFilter(
                    DataType::INTEGER,
                    'The number of items per page.',
                    null !== $pageSize ? $pageSize : $this->getDefaultPageSize()
                )
            );
        }
    }

    /**
     * @return int
     */
    protected function getDefaultPageSize()
    {
        return self::DEFAULT_PAGE_SIZE;
    }
}
