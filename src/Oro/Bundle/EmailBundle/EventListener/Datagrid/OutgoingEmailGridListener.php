<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridResultHelper;

/**
 * Handles outgoing email grid data enrichment with recipient information.
 *
 * Listens to datagrid result events to add recipient email addresses to outgoing email records,
 * providing a complete view of email recipients in the outgoing emails grid.
 */
class OutgoingEmailGridListener
{
    /** @var EmailGridResultHelper */
    protected $resultHelper;

    public function __construct(EmailGridResultHelper $resultHelper)
    {
        $this->resultHelper = $resultHelper;
    }

    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $this->resultHelper->addEmailRecipients($records);
    }
}
