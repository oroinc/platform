<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

/**
 * Defines the contract for datagrid events.
 *
 * This interface marks events that are related to datagrid processing and provides access
 * to the datagrid instance. Event listeners can use this to modify datagrid behavior at
 * various stages of the datagrid lifecycle.
 */
interface GridEventInterface
{
    /**
     * Getter for datagrid
     *
     * @return DatagridInterface
     */
    public function getDatagrid();
}
