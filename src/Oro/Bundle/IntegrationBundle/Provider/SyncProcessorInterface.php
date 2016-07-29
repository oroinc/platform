<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface SyncProcessorInterface
{
    /**
     * @param Channel $channel
     * @param $connector
     * @param array $connectorParameters
     *
     * @return bool
     */
    public function process(Channel $channel, $connector, array $connectorParameters = []);
}
