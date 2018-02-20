<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class OrmDatasourceAclListener
{
    const EDIT_SCOPE = 'edit';

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
     * @param OrmResultBefore $event
     */
    public function onResultBefore(OrmResultBefore $event)
    {
        $dataGrid = $event->getDatagrid();
        $config = $dataGrid->getConfig();
        if (!$config->isDatasourceSkipAclApply()) {
            $permission = $this->getPermission($dataGrid->getScope());
            $this->aclHelper->apply($event->getQuery(), $permission);
        }
    }

    /**
     * @param string|null $scope
     *
     * @return string
     */
    protected function getPermission($scope)
    {
        if (self::EDIT_SCOPE === $scope) {
            return 'EDIT';
        }

        return 'VIEW';
    }
}
