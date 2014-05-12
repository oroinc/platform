<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

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
        $this->aclHelper->apply($event->getQuery());
    }
}
