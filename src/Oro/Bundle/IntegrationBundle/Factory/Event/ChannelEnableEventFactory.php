<?php

namespace Oro\Bundle\IntegrationBundle\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelEnableEvent;

/**
 * Creates channel enable events.
 *
 * This factory is responsible for instantiating {@see ChannelEnableEvent} objects with the
 * appropriate channel data, ensuring consistent event creation for channel enable actions.
 */
class ChannelEnableEventFactory implements ChannelActionEventFactoryInterface
{
    #[\Override]
    public function create(Channel $channel)
    {
        return new ChannelEnableEvent($channel);
    }
}
