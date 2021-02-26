<?php

namespace Oro\Bundle\MigrationBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Tools\SchemaTrait;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Substitutes SchemaDiff service for "doctrine:schema:update" and "doctrine:schema:validate" CLI commands.
 */
class ConsoleCommandListener
{
    use SchemaTrait;

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $commandName = $event->getCommand()->getName();
        if ('doctrine:schema:update' === $commandName || 'doctrine:schema:validate' === $commandName) {
            $this->overrideSchemaDiff();
        }
    }
}
