<?php

namespace Oro\Bundle\SyncBundle\WebsocketServerState;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Manages the state of the Websocket server by storing and retrieving the last updated timestamp
 * for a given state ID in the application cache.
 */
class WebsocketServerCacheStateManager implements WebsocketServerStateManagerInterface
{
    public function __construct(
        private readonly CacheItemPoolInterface $websocketServerCacheState
    ) {
    }

    #[\Override]
    public function updateState(string $stateId): \DateTimeInterface
    {
        $websocketServerCacheState = $this->websocketServerCacheState->getItem($stateId);
        $stateDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $websocketServerCacheState->set($stateDate);
        $this->websocketServerCacheState->save($websocketServerCacheState);

        return $stateDate;
    }

    #[\Override]
    public function getState(string $stateId): ?\DateTimeInterface
    {
        $websocketServerCacheState = $this->websocketServerCacheState->getItem($stateId);

        if (!$websocketServerCacheState->isHit()) {
            return null;
        }

        return $websocketServerCacheState->get();
    }
}
