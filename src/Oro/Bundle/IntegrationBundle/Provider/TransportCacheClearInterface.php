<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

/**
 * Defines the contract for transports that support cache clearing.
 *
 * Implementations of this interface can clear cached data from the remote integration service,
 * either for a specific URL or globally. This is useful for ensuring that stale data is
 * refreshed when needed.
 */
interface TransportCacheClearInterface
{
    /**
     * @param mixed|null $url
     *
     * @return void
     */
    public function cacheClear($url = null);
}
