<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridHelper;

/**
 * For the main user's email grid. For the logged in user it is My Emails menu
 */
class MainUserEmailGridListener
{
    /** @var EmailGridHelper */
    protected $emailGridHelper;

    /**
     * @param EmailGridHelper $emailGridHelper
     */
    public function __construct(EmailGridHelper $emailGridHelper)
    {
        $this->emailGridHelper = $emailGridHelper;
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
            $userId     = $parameters->get('userId');
            $this->emailGridHelper->updateDatasource($datasource, $userId);
            $this->emailGridHelper->handleRefresh($parameters, $userId);
        }
    }
}
