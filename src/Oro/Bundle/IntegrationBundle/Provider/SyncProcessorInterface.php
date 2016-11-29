<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

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
