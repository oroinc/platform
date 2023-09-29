<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\EventListener;

use Oro\Bundle\SyncBundle\WebSocket\DsnBasedParameters;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Disables gos:websocket:server command if websocket server host parameter is empty.
 */
class WebsocketServerCommandListener
{
    private DsnBasedParameters $dsnParameters;

    private string $commandName;

    public function __construct(DsnBasedParameters $dsnParameters, string $commandName)
    {
        $this->dsnParameters = $dsnParameters;
        $this->commandName = $commandName;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        if ($event->getCommand()?->getName() !== $this->commandName) {
            return;
        }

        if ($this->dsnParameters->getHost() === '') {
            $event->disableCommand();

            $io = new SymfonyStyle($event->getInput(), $event->getOutput());
            $io->note(
                'Websocket server is not configured. '
                . 'Ensure that the following environment variables are not empty:' . PHP_EOL
                . ' - ORO_WEBSOCKET_SERVER_DSN' . PHP_EOL
                . ' - ORO_WEBSOCKET_FRONTEND_DSN' . PHP_EOL
                . ' - ORO_WEBSOCKET_BACKEND_DSN'
            );
        }
    }
}
