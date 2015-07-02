<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridHelper;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

class ActivityGridListener
{
    /** @var EmailGridHelper */
    protected $emailGridHelper;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /**
     * @param EmailGridHelper     $emailGridHelper
     * @param EntityRoutingHelper $entityRoutingHelper
     */
    public function __construct(EmailGridHelper $emailGridHelper, EntityRoutingHelper $entityRoutingHelper)
    {
        $this->emailGridHelper     = $emailGridHelper;
        $this->entityRoutingHelper = $entityRoutingHelper;
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
            $entityClass = $this->entityRoutingHelper->resolveEntityClass($parameters->get('entityClass'));
            $entityId = $parameters->get('entityId');

            $this->emailGridHelper->updateDatasource($datasource, $entityId, $entityClass);
            if ($this->emailGridHelper->isUserEntity($entityClass)) {
                $this->emailGridHelper->handleRefresh($parameters, $entityId);
            }
        }
    }
}
