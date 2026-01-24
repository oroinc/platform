<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched at the start of datagrid building process.
 *
 * This event is fired before the datagrid builder begins constructing the datagrid instance.
 * Event listeners can validate and modify the datagrid configuration and access request parameters
 * to customize the grid structure before it is built.
 */
class PreBuild extends Event implements GridConfigurationEventInterface
{
    const NAME = 'oro_datagrid.datagrid.build.pre';

    /** @var DatagridConfiguration */
    protected $config;

    /** @var ParameterBag */
    protected $parameters;

    public function __construct(DatagridConfiguration $config, ParameterBag $parameters)
    {
        $this->config     = $config;
        $this->parameters = $parameters;
    }

    /**
     * @return ParameterBag
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    #[\Override]
    public function getConfig()
    {
        return $this->config;
    }
}
