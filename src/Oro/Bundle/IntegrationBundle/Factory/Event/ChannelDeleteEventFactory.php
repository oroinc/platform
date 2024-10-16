<?php

namespace Oro\Bundle\IntegrationBundle\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDeleteEvent;

class ChannelDeleteEventFactory implements ChannelActionEventFactoryInterface
{
    #[\Override]
    public function create(Channel $channel)
    {
        return new ChannelDeleteEvent($channel);
    }
}
