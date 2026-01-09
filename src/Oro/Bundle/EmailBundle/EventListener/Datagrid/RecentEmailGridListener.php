<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridHelper;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;

/**
 * Handles recent email grid configuration with ACL and sender information.
 *
 * Listens to datagrid build events to apply ACL filtering and add sender email addresses
 * to recent email records, ensuring users only see emails they have access to.
 */
class RecentEmailGridListener
{
    /** @var EmailGridHelper */
    protected $emailGridHelper;

    /** @var  EmailQueryFactory */
    protected $emailQueryFactory;

    public function __construct(EmailGridHelper $emailGridHelper, EmailQueryFactory $emailQueryFactory)
    {
        $this->emailGridHelper = $emailGridHelper;
        $this->emailQueryFactory = $emailQueryFactory;
    }

    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $datasource */
        $datasource = $event->getDatagrid()->getDatasource();
        $this->emailQueryFactory->applyAcl($datasource->getQueryBuilder());
    }
}
