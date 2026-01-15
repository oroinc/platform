<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\WebsocketServerState;

/**
 * Manages the state of the Websocket server.
 */
interface WebsocketServerStateManagerInterface
{
    public function updateState(string $stateId): \DateTimeInterface;

    public function getState(string $stateId): ?\DateTimeInterface;
}
