<?php

namespace Oro\Bundle\SyncBundle\Authentication\Origin;

/**
 * Interface to describe origin providers which will be used during origin checking in a websocket server.
 */
interface OriginProviderInterface
{
    /**
     * @return string[]
     */
    public function getOrigins(): array;
}
