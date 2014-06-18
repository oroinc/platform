<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\ActivityBundle\Tools\ActivityHelper;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

class ActivityGridListener
{
    /** @var ActivityHelper */
    protected $activityHelper;

    /**
     * @param ActivityHelper $activityHelper
     */
    public function __construct(ActivityHelper $activityHelper)
    {
        $this->activityHelper = $activityHelper;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $parameters = $datagrid->getParameters();
            $this->activityHelper->addFilterByTargetEntity(
                $datasource->getQueryBuilder(),
                $parameters->get('entity')
            );
        }
    }
}
