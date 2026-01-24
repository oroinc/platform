<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Filters widget data by owner criteria based on widget configuration.
 *
 * This filter applies owner-based restrictions to widget query builders, limiting
 * the displayed data to records owned by specific users or business units as configured
 * in the widget options. It uses optimized `IN` queries to efficiently filter large datasets
 * while respecting the ownership hierarchy and user permissions.
 */
class OwnersWidgetProviderFilter implements WidgetProviderFilterInterface
{
    /** @var OwnerHelper */
    protected $ownerHelper;

    public function __construct(OwnerHelper $ownerHelper)
    {
        $this->ownerHelper = $ownerHelper;
    }

    #[\Override]
    public function filter(QueryBuilder $queryBuilder, WidgetOptionBag $widgetOptions)
    {
        $owners = $this->ownerHelper->getOwnerIds($widgetOptions);
        $alias = QueryBuilderUtil::getSingleRootAlias($queryBuilder, false);
        if ($owners) {
            // check if options are for opportunity_by_status
            QueryBuilderUtil::applyOptimizedIn($queryBuilder, $alias.'.owner', $owners);
        }
    }
}
