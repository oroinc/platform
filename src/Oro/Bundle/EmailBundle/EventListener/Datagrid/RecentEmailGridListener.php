<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridHelper;

class RecentEmailGridListener
{
    /** @var EmailGridHelper */
    protected $emailGridHelper;

    /**
     * @param EmailGridHelper        $emailGridHelper
     */
    public function __construct(EmailGridHelper $emailGridHelper)
    {
        $this->emailGridHelper   = $emailGridHelper;
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
            $userId = $parameters->get('userId');

            $this->emailGridHelper->updateDatasource($datasource, $userId);

            $queryBuilder = $datasource->getQueryBuilder();

            // bind 'origin_ids' parameter
            $originIds    = [];
            $emailOrigins = $this->emailGridHelper->getEmailOrigins($userId);
            foreach ($emailOrigins as $emailOrigin) {
                $originIds[] = $emailOrigin->getId();
            }
            $queryBuilder->setParameter('origin_ids', $originIds);
        }
    }
}
