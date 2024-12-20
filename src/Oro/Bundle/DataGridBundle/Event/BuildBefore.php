<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class BuildBefore
 * @package Oro\Bundle\DataGridBundle\Event
 *
 * This event dispatched before datagrid builder starts build datagrid
 * Listeners could apply validation of config and provide changes of config
 */
class BuildBefore extends Event implements GridEventInterface, GridConfigurationEventInterface
{
    const NAME = 'oro_datagrid.datagrid.build.before';

    /** @var DatagridInterface */
    protected $datagrid;

    /** @var DatagridConfiguration */
    protected $config;

    public function __construct(DatagridInterface $datagrid, DatagridConfiguration $config)
    {
        $this->datagrid   = $datagrid;
        $this->config     = $config;
    }

    #[\Override]
    public function getDatagrid()
    {
        return $this->datagrid;
    }

    #[\Override]
    public function getConfig()
    {
        return $this->config;
    }
}
