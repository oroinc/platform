<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

/**
 * Defines the contract for connectors that support bidirectional synchronization.
 *
 * Connectors implementing this interface can synchronize data in both directions:
 * importing data from the remote system and exporting data back to it. This allows
 * for maintaining data consistency between the application and the remote system.
 * The interface defines constants for conflict resolution strategies (remote wins vs local wins).
 */
interface TwoWaySyncConnectorInterface extends ConnectorInterface
{
    const REMOTE_WINS = 'remote';
    const LOCAL_WINS  = 'local';

    /**
     * @return string
     */
    public function getExportJobName();
}
