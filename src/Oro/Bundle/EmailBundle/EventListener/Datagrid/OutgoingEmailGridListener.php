<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridResultHelper;

class OutgoingEmailGridListener
{
    /** @var EmailGridResultHelper */
    protected $resultHelper;

    /**
     * @param EmailGridResultHelper $resultHelper
     */
    public function __construct(EmailGridResultHelper $resultHelper)
    {
        $this->resultHelper = $resultHelper;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $this->resultHelper->addEmailRecipients($records);
    }
}
