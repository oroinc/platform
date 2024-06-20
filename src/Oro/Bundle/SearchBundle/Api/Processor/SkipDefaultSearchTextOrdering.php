<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultOrdering;
use Oro\Bundle\SearchBundle\Api\Filter\SimpleSearchFilter;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Skip setting of default ordering when data are filtered by "searchText" filter.
 */
class SkipDefaultSearchTextOrdering implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->isProcessed(SetDefaultOrdering::OPERATION_NAME)) {
            return;
        }

        if ($this->hasSearchTextFilter($context->getFilters(), $context->getFilterValues())) {
            $context->setProcessed(SetDefaultOrdering::OPERATION_NAME);
        }
    }

    private function hasSearchTextFilter(
        FilterCollection $filterCollection,
        FilterValueAccessorInterface $filterValueAccessor
    ): bool {
        foreach ($filterCollection as $filterKey => $filter) {
            if ($filter instanceof SimpleSearchFilter && $filterValueAccessor->has($filterKey)) {
                return true;
            }
        }

        return false;
    }
}
