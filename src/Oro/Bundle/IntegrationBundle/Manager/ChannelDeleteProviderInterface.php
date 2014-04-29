<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface ChannelDeleteProviderInterface
{
    /**
     * Is this provider supports given channel type
     * @param string $channelType
     *
     * @return bool
     */
    public function isSupport($channelType);

    /**
     * Process delete channel related data
     *
     * @param Channel $channel
     */
    public function deleteRelatedData(Channel $channel);
}
