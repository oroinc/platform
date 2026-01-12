<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

/**
 * Defines the contract for connectors that support force synchronization.
 *
 * Connectors implementing this interface can perform a full synchronization of all data
 * from the remote system, regardless of the last synchronization time. This is useful
 * for scenarios where a complete data refresh is needed.
 */
interface ForceConnectorInterface
{
    /**
     * Returns whether connector supports force sync or no
     *
     * @return bool
     */
    public function supportsForceSync();
}
