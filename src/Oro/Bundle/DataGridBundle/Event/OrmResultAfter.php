<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class OrmResultAfter extends AbstractResultAfter implements GridEventInterface
{
    const NAME = 'oro_datagrid.orm_datasource.result.after';
}
