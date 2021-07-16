<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

/**
 * Provides a way to filter widget options using all registered filters.
 */
class WidgetProviderFilterManager
{
    /** @var iterable|WidgetProviderFilterInterface[] */
    private $filters;

    /**
     * @param iterable|WidgetProviderFilterInterface[] $filters
     */
    public function __construct(iterable $filters)
    {
        $this->filters = $filters;
    }

    public function filter(QueryBuilder $queryBuilder, WidgetOptionBag $widgetOptions)
    {
        foreach ($this->filters as $filter) {
            $filter->filter($queryBuilder, $widgetOptions);
        }
    }
}
