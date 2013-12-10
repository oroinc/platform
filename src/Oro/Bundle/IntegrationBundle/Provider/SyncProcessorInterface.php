<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface SyncProcessorInterface
{
    /**
     * @param Channel $channel
     * @param bool    $isValidationOnly
     *
     * @return mixed
     */
    public function process(Channel $channel, $isValidationOnly = false);
}
