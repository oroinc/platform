<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;

/**
 * Implementation of this interface allows to have some logic to skip processing of this connector.
 */
interface AllowedConnectorInterface extends ConnectorInterface
{
    /**
     * This method can be used to skip processing of this connector.
     *
     * @param Channel  $integration
     * @param Status[] $processedConnectorsStatuses Array of connector sync statuses which was processed before
     *
     * @return bool
     */
    public function isAllowed(Channel $integration, array $processedConnectorsStatuses);
}
