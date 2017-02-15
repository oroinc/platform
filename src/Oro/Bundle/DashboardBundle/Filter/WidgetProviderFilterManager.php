<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class WidgetProviderFilterManager
{
    /** @var WidgetProviderFilterInterface[] */
    protected $filters = [];

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param WidgetProviderFilterInterface $filter
     */
    public function addFilter(WidgetProviderFilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @param  QueryBuilder    $queryBuilder
     * @param  WidgetOptionBag $widgetOptions
     * @return Query
     */
    public function filter(QueryBuilder $queryBuilder, WidgetOptionBag $widgetOptions)
    {
        foreach ($this->filters as $filter) {
            $filter->filter($queryBuilder, $widgetOptions);
        }

        return $this->aclHelper->apply($queryBuilder);
    }
}
