<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;

interface AllowedConnectorInterface
{
    /**
     * @param Channel  $integration
     * @param Status[] $processedConnectorsStatuses Array of connector sync statuses which was processed before
     *
     * @return bool
     */
    public function isAllowed(Channel $integration, array $processedConnectorsStatuses);
}
