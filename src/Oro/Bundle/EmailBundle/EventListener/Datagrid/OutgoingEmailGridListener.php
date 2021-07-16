<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridResultHelper;

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
