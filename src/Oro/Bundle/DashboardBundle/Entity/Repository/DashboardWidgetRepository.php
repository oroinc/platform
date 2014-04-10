<?php

namespace Oro\Bundle\DashboardBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class DashboardWidgetRepository extends EntityRepository
{
    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @return DashboardWidget[]
     */
    public function getAvailableWidgets()
    {
        $qb = $this->createQueryBuilder('w');
        return $this->aclHelper->apply($qb)->execute();
    }
}
