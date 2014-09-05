<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class OrmDatasourceAclListener
{
    /**
     * @var AclHelper
     */
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
        $config = $event->getDatagrid()->getConfig();
        if (!$config->offsetGetByPath(Builder::DATASOURCE_SKIP_ACL_WALKER_PATH, false)) {
            $this->aclHelper->apply($event->getQuery());
        }
    }
}
