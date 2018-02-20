<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Filter\WidgetProviderFilterInterface;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

class TestFilter implements WidgetProviderFilterInterface
{
    /**
     * @param  QueryBuilder $queryBuilder
     * @param  WidgetOptionBag $widgetOptions
     */
    public function filter(QueryBuilder $queryBuilder, WidgetOptionBag $widgetOptions)
    {
        // modify queryBuilder
        $queryBuilder->addSelect();
    }
}
