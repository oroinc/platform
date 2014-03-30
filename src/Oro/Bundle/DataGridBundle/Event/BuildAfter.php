<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

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

    /** @var array */
    protected $parameters;

    public function __construct(DatagridInterface $datagrid, array $parameters = array())
    {
        $this->datagrid = $datagrid;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatagrid()
    {
        return $this->datagrid;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
