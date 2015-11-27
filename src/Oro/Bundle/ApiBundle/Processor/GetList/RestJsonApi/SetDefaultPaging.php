<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\RestJsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\PageNumberFilter;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\DataType;

class SetDefaultPaging implements ProcessorInterface
{
    const DEFAULT_PAGE      = 1;
    const DEFAULT_PAGE_SIZE = 10;

    const PAGE_FILTER_KEY      = 'page[number]';
    const PAGE_SIZE_FILTER_KEY = 'page[size]';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $filters = $context->getFilters();
        if (!$filters->has(self::PAGE_SIZE_FILTER_KEY)) {
            $filters->add(
                self::PAGE_SIZE_FILTER_KEY,
                new PageSizeFilter(
                    DataType::INTEGER,
                    'The number of items per page.',
                    self::DEFAULT_PAGE_SIZE
                )
            );
        }
        // "page number" filter must be added after "page size" filter because it depends on this filter
        // @see Oro\Bundle\ApiBundle\Filter\PageNumberFilter::apply
        if (!$filters->has(self::PAGE_FILTER_KEY)) {
            $filters->add(
                self::PAGE_FILTER_KEY,
                new PageNumberFilter(
                    DataType::UNSIGNED_INTEGER,
                    'The page number, starting from 1.',
                    self::DEFAULT_PAGE
                )
            );
        } else {
            // make sure that "page number" filter is added after "page size" filter
            $pageFilter = $filters->get(self::PAGE_FILTER_KEY);
            $filters->remove(self::PAGE_FILTER_KEY);
            $filters->add(self::PAGE_FILTER_KEY, $pageFilter);
        }
    }
}
