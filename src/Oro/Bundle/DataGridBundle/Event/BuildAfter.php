<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class BuildBefore
 * @package Oro\Bundle\DataGridBundle\Event
 *
 * This event dispatched after datagrid builder finish building datasource for datagrid
 */
class BuildAfter extends Event implements GridEventInterface
{
    const NAME = 'oro_datagrid.datagrid.build.after';

    /** @var DatagridInterface */
    protected $datagrid;

    public function __construct(DatagridInterface $datagrid)
    {
        $this->datagrid = $datagrid;
    }

    #[\Override]
    public function getDatagrid()
    {
        return $this->datagrid;
    }
}
