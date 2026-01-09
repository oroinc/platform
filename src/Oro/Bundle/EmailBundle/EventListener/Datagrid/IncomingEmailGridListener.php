<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;

/**
 * Handles incoming email grid configuration and data enrichment.
 *
 * Listens to datagrid build events to add sender email address information to incoming email records,
 * enhancing the display of email data in the incoming emails grid.
 */
class IncomingEmailGridListener
{
    /** @var  EmailQueryFactory */
    protected $emailQueryFactory;

    public function __construct(EmailQueryFactory $emailQueryFactory)
    {
        $this->emailQueryFactory = $emailQueryFactory;
    }

    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $datasource */
        $datasource = $event->getDatagrid()->getDatasource();
        $this->emailQueryFactory->addFromEmailAddress($datasource->getQueryBuilder());
    }
}
