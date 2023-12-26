<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

/**
 * Abstraction for handling datagrid mass action logic.
 */
interface MassActionHandlerInterface
{
    /**
     * Handle mass action
     */
    public function handle(MassActionHandlerArgs $args): MassActionResponseInterface;
}
