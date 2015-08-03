<?php

namespace Oro\Bundle\MigrationBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;

class ConsoleCommandListener
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if ('doctrine:schema:update' === $event->getCommand()->getName()) {
            // substitute the class 'Doctrine\DBAL\Schema\SchemaDiff'
            // with 'Oro\Bundle\MigrationBundle\Migration\Schema\SchemaDiff'
            $schemaDiffPath = realpath(__DIR__ . '/../Migration/Schema/SchemaDiff.php');
            spl_autoload_register(
                function ($class) use ($schemaDiffPath) {
                    if ('Doctrine\DBAL\Schema\SchemaDiff' === $class) {
                        require $schemaDiffPath;

                        return true;
                    }

                    return false;
                },
                true,
                true
            );
        }
    }
}
