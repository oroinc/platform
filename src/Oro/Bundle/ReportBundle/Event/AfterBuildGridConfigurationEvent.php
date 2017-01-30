<?php

namespace Oro\Bundle\ReportBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class AfterBuildGridConfigurationEvent extends Event
{
    const NAME = 'after_build_grid_configuration_event';

    /**
     * @var DatagridConfiguration
     */
    protected $configuration;

    /**
     * @var mixed|null
     */
    protected $context;

    /**
     * @var mixed
     */
    protected $source;

    /**
     * @param DatagridConfiguration $configuration
     * @param mixed $source
     * @param mixed|null $context
     */
    public function __construct(DatagridConfiguration $configuration, $source,  $context = null)
    {
        $this->configuration = $configuration;
        $this->context = $context;
        $this->source = $source;
    }

    /**
     * @return DatagridConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return mixed|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }
}
