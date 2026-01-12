<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before the datagrid builder starts building the datagrid.
 *
 * This event is fired after the datagrid instance is created but before the builder processes the configuration.
 * Event listeners can validate and modify the datagrid configuration and access the datagrid instance
 * to apply customizations before the build process begins.
 */
class BuildBefore extends Event implements GridEventInterface, GridConfigurationEventInterface
{
    public const NAME = 'oro_datagrid.datagrid.build.before';

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
