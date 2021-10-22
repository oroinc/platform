<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Applies ACL restriction to the datagrid query.
 */
class OrmDatasourceAclListener
{
    /** @deprecated   */
    const EDIT_SCOPE = 'edit';

    protected AclHelper $aclHelper;

    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    public function onResultBefore(OrmResultBefore $event)
    {
        $config = $event->getDatagrid()->getConfig();
        if (!$config->isDatasourceSkipAclApply()) {
            $this->aclHelper->apply($event->getQuery(), $config->getDatasourceAclApplyPermission());
        }
    }

    /**
     * @param string|null $scope
     *
     * @return string
     *
     * @deprecated
     */
    protected function getPermission($scope)
    {
        if (self::EDIT_SCOPE === $scope) {
            return 'EDIT';
        }

        return 'VIEW';
    }
}
