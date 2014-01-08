<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

interface MassActionHandlerInterface
{
    /**
     * Handle mass action
     *
     * @param MassActionHandlerArgs $args
     *
     * @return MassActionResponseInterface
     */
    public function handle(MassActionHandlerArgs $args);
}
