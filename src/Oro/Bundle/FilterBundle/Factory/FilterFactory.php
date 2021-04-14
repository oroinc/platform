<?php

namespace Oro\Bundle\FilterBundle\Factory;

use Oro\Bundle\FilterBundle\Filter\FilterBagInterface;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

/**
 * Provides an instance of initialized filter.
 */
class FilterFactory
{
    private FilterBagInterface $filterBag;

    public function __construct(FilterBagInterface $filterBag)
    {
        $this->filterBag = $filterBag;
    }

    public function createFilter(string $filterName, array $filterConfig): FilterInterface
    {
        if (empty($filterConfig[FilterUtility::TYPE_KEY])) {
            throw new \InvalidArgumentException(
                sprintf('The filter config was expected to contain "%s" key', FilterUtility::TYPE_KEY)
            );
        }

        $filter = $this->filterBag->getFilter($filterConfig[FilterUtility::TYPE_KEY]);
        $filter->init($filterName, $filterConfig);

        // Ensures filter is "somewhat-stateless" across datagrids.
        // "Somewhat stateless" means that some filters cannot be fully stateless, because there are filters that
        // are used directly as a service, e.g. oro_filter.date_grouping_filter. That is why we cannot clone filter
        // before calling "init".
        return clone $filter;
    }
}
