<?php

namespace Oro\Bundle\OrganizationBundle\Event;

use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;

/**
 * Interface for ImportStrategyListener allows to easily decorate listener.
 */
interface ImportStrategyListenerInterface
{
    /**
     * @param StrategyEvent $event
     */
    public function onProcessAfter(StrategyEvent $event);

    /**
     * Clear default organization on doctrine entity manager clear
     */
    public function onClear();
}
