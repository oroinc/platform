<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Stub;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\GridEventInterface;
use Symfony\Component\EventDispatcher\Event;

class GridEvent extends Event implements GridEventInterface
{
    protected $datagrid;

    public function __construct(DatagridInterface $datagrid)
    {
        $this->datagrid = $datagrid;
    }

    public function getDatagrid()
    {
        return $this->datagrid;
    }
}
