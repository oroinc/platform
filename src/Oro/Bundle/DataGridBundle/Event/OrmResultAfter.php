<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Doctrine\ORM\AbstractQuery;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * Class ResultAfter
 * @package Oro\Bundle\DataGridBundle\Event
 *
 * This event dispatched after datagrid builder finish building result
 */
class OrmResultAfter extends AbstractResultAfter
{
    const NAME = 'oro_datagrid.orm_datasource.result.after';
}
