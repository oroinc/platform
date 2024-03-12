<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\SecurityBundle\AccessRule\AclAccessRule;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Applies ACL restriction to the datagrid query.
 */
class OrmDatasourceAclListener
{
    protected AclHelper $aclHelper;

    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    public function onResultBefore(OrmResultBefore $event)
    {
        $config = $event->getDatagrid()->getConfig();
        if (!$config->isDatasourceSkipAclApply()) {
            $this->aclHelper->apply(
                $event->getQuery(),
                $config->getDatasourceAclApplyPermission(),
                [
                    AclAccessRule::CONDITION_DATA_BUILDER_CONTEXT =>
                        $config->offsetGetByPath(DatagridConfiguration::ACL_CONDITION_DATA_BUILDER_CONTEXT, [])
                ]
            );
        }
    }
}
