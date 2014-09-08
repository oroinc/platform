<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Stub;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\GridConfigurationEventInterface;
use Symfony\Component\EventDispatcher\Event;

class GridConfigEvent extends Event implements GridConfigurationEventInterface
{
    protected $configuration;

    public function __construct(DatagridConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfig()
    {
        return $this->configuration;
    }
}
