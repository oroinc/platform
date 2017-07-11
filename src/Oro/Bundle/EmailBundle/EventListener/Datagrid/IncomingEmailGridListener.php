<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;

class IncomingEmailGridListener
{
    /** @var  EmailQueryFactory */
    protected $emailQueryFactory;

    /**
     * @param EmailQueryFactory $emailQueryFactory
     */
    public function __construct(EmailQueryFactory $emailQueryFactory)
    {
        $this->emailQueryFactory = $emailQueryFactory;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $datasource */
        $datasource = $event->getDatagrid()->getDatasource();
        $this->emailQueryFactory->addFromEmailAddress($datasource->getQueryBuilder());
    }
}
