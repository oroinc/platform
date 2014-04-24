<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface ChannelDeleteProviderInterface
{
    /**
     * Get suppoted Channel type
     * @return string
     */
    public function getSupportedChannelType();

    /**
     * Process delete channel
     *
     * @param Channel $channel
     * @return bool
     */
    public function processDelete(Channel $channel);
}
