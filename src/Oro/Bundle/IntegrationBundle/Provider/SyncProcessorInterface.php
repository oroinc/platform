<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

/**
 * Defines the contract for processors that handle integration synchronization.
 *
 * Implementations of this interface are responsible for executing the synchronization
 * process for a specific integration channel and connector. They coordinate the data
 * retrieval from the remote system and the import/export of data within the application.
 */
interface SyncProcessorInterface
{
    /**
     * @param Integration $integration
     * @param $connector
     * @param array $connectorParameters
     *
     * @return bool
     */
    public function process(Integration $integration, $connector, array $connectorParameters = []);
}
