<?php

namespace Oro\Bundle\SyncBundle\EventListener\SystemConfig;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SyncBundle\WebsocketServerState\WebsocketServerStateManagerInterface;
use Oro\Bundle\SyncBundle\WebsocketServerState\WebsocketServerStates;

/**
 * Updates the system_config websocket server state on system configuration changes.
 */
class WebsocketServerStateSystemConfigUpdateListener
{
    public function __construct(
        private readonly ApplicationState $applicationState,
        private readonly WebsocketServerStateManagerInterface $websocketServerStateManager
    ) {
    }

    public function onConfigUpdate(ConfigUpdateEvent $event): void
    {
        if (!$this->applicationState->isInstalled()) {
            return;
        }

        try {
            $this->websocketServerStateManager->updateState(WebsocketServerStates::SYSTEM_CONFIG);
        } catch (TableNotFoundException $exception) {
            // Listener is triggered during application upgrade - from the point when the related table
            // is not yet created.
        }
    }
}
