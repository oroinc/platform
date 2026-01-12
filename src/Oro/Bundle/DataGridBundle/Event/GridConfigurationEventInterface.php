<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

/**
 * Defines the contract for datagrid configuration events.
 *
 * This interface marks events that provide access to datagrid configuration during the
 * configuration phase. Event listeners can use this to modify or extend datagrid configuration
 * before the datagrid is built and processed.
 */
interface GridConfigurationEventInterface
{
    /**
     * Getter for datagrid configuration
     *
     * @return DatagridConfiguration
     */
    public function getConfig();
}
