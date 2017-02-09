<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Doctrine\ORM\QueryBuilder;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

class WidgetProviderFilter
{
    /** @var AclHelper */
    protected $aclHelper;

    /** @var OwnerHelper */
    protected $ownerHelper;

    public function __construct(AclHelper $aclHelper, OwnerHelper $ownerHelper)
    {
        $this->aclHelper   = $aclHelper;
        $this->ownerHelper = $ownerHelper;
    }

    public function filter(QueryBuilder $queryBuilder, WidgetOptionBag $widgetOptions)
    {
        $this->processOwners($queryBuilder, $widgetOptions);

        return $this->applyAcl($queryBuilder);
    }

    protected function processOwners(QueryBuilder $queryBuilder, WidgetOptionBag $widgetOptions)
    {
        $owners = $this->ownerHelper->getOwnerIds($widgetOptions);

        if ($owners) {
            // check if options are for opportunity_by_status
            QueryUtils::applyOptimizedIn($queryBuilder, 'o.owner', $owners);
        }
    }

    protected function applyAcl(QueryBuilder $queryBuilder)
    {
        return $this->aclHelper->apply($queryBuilder)->getArrayResult();
    }
}
