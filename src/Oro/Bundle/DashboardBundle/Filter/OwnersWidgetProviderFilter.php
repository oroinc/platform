<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class OwnersWidgetProviderFilter implements WidgetProviderFilterInterface
{
    /** @var OwnerHelper */
    protected $ownerHelper;

    /**
     * @param OwnerHelper $ownerHelper
     */
    public function __construct(OwnerHelper $ownerHelper)
    {
        $this->ownerHelper = $ownerHelper;
    }

    /**
     * {@inheritdoc}
     */
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
