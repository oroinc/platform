<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\GridConfigurationEventInterface;

class ConfigureActionsBefore extends Event implements GridConfigurationEventInterface
{
    const NAME = 'oro_datagrid.datagrid.extension.action.configure-actions.before';

    /** @var DatagridConfiguration */
    protected $config;

    /**
     * @param DatagridConfiguration $config
     */
    public function __construct(DatagridConfiguration $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return $this->config;
    }
}
