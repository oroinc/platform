<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * A base class that can be used to create a processor to set default paging for different kind of requests.
 */
abstract class SetDefaultPaging implements ProcessorInterface
{
    const DEFAULT_PAGE_SIZE = 10;

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

        $filters = $context->getFilters();
        $this->addPageSizeFilter($filters);
        $this->addPageNumberFilter($filters);
    }

    /**
     * @param FilterCollection $filters
     */
    protected function addPageNumberFilter(FilterCollection $filters)
    {
        // "page number" filter must be added after "page size" filter because it depends on this filter
        // @see Oro\Bundle\ApiBundle\Filter\PageNumberFilter::apply
        $pageNumberFilterKey = $this->getPageNumberFilterKey();
        if (!$filters->has($pageNumberFilterKey)) {
            $filters->add(
                $pageNumberFilterKey,
                new PageNumberFilter(
                    DataType::UNSIGNED_INTEGER,
                    'The page number, starting from 1.',
                    1
                )
            );
        } else {
            // make sure that "page number" filter is added after "page size" filter
            $pageFilter = $filters->get($pageNumberFilterKey);
            $filters->remove($pageNumberFilterKey);
            $filters->add($pageNumberFilterKey, $pageFilter);
        }
    }

    /**
     * @param FilterCollection $filters
     */
    protected function addPageSizeFilter(FilterCollection $filters)
    {
        $pageSizeFilterKey = $this->getPageSizeFilterKey();
        if (!$filters->has($pageSizeFilterKey)) {
            $filters->add(
                $pageSizeFilterKey,
                new PageSizeFilter(
                    DataType::INTEGER,
                    'The number of items per page.',
                    $this->getDefaultPageSize()
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

    /**
     * @return string
     */
    abstract protected function getPageNumberFilterKey();

    /**
     * @return string
     */
    abstract protected function getPageSizeFilterKey();
}
