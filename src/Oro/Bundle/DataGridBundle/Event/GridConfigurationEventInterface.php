<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

interface GridConfigurationEventInterface
{
    /**
     * Getter for datagrid configuration
     *
     * @return DatagridConfiguration
     */
    public function getConfig();
}
