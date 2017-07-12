<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridHelper;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;

class RecentEmailGridListener
{
    /** @var EmailGridHelper */
    protected $emailGridHelper;

    /** @var  EmailQueryFactory */
    protected $emailQueryFactory;

    /**
     * @param EmailGridHelper   $emailGridHelper
     * @param EmailQueryFactory $emailQueryFactory
     */
    public function __construct(EmailGridHelper $emailGridHelper, EmailQueryFactory $emailQueryFactory)
    {
        $this->emailGridHelper = $emailGridHelper;
        $this->emailQueryFactory = $emailQueryFactory;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $datasource */
        $datasource = $event->getDatagrid()->getDatasource();
        $this->emailQueryFactory->applyAcl($datasource->getQueryBuilder());
    }
}
