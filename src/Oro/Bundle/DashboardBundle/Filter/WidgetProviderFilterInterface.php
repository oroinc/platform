<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

/**
 * Contract for classes that filter widget providers' data
 */
interface WidgetProviderFilterInterface
{
    /**
     * @param  QueryBuilder    $queryBuilder
     * @param  WidgetOptionBag $widgetOptions
     */
    public function filter(QueryBuilder $queryBuilder, WidgetOptionBag $widgetOptions);
}
