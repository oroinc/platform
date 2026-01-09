<?php

namespace Oro\Bundle\IntegrationBundle\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDeleteEvent;

/**
 * Creates channel delete events.
 *
 * This factory is responsible for instantiating {@see ChannelDeleteEvent} objects with the
 * appropriate channel data, ensuring consistent event creation for channel deletion actions.
 */
class ChannelDeleteEventFactory implements ChannelActionEventFactoryInterface
{
    #[\Override]
    public function create(Channel $channel)
    {
        return new ChannelDeleteEvent($channel);
    }
}
