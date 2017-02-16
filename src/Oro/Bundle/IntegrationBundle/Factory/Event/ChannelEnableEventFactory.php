<?php

namespace Oro\Bundle\IntegrationBundle\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelEnableEvent;

class ChannelEnableEventFactory implements ChannelActionEventFactoryInterface
{
    /**
     * @param Channel $channel
     *
     * @return ChannelEnableEvent
     */
    public function create(Channel $channel)
    {
        return new ChannelEnableEvent($channel);
    }
}
