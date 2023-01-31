<?php

namespace Oro\Bundle\SyncBundle\WebSocket;

use Gos\Bundle\WebSocketBundle\Command\WebsocketServerCommand;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\ServerLauncherInterface;

/**
 * Gos websocket command factory
 */
class WebsocketServerCommandFactory
{
    public function createGosWebsocketCommand(
        ServerLauncherInterface $entryPoint,
        DsnBasedParameters $dsnParameters,
        ServerRegistry $serverRegistry = null
    ): WebsocketServerCommand {
        return new WebsocketServerCommand(
            $entryPoint,
            $dsnParameters->getHost(),
            $dsnParameters->getPort(),
            $serverRegistry
        );
    }
}
