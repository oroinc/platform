<?php

namespace Oro\Bundle\MigrationBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Tools\SchemaTrait;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

class ConsoleCommandListener
{
    use SchemaTrait;

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if ('doctrine:schema:update' === $event->getCommand()->getName()) {
            $this->overrideSchemaDiff();
        }
    }
}
