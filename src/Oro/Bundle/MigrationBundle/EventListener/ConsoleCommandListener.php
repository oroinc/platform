<?php

namespace Oro\Bundle\MigrationBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;

use Oro\Bundle\EntityExtendBundle\Tools\SchemaTrait;

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
