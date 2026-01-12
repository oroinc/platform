<?php

namespace Oro\Bundle\IntegrationBundle\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDisableEvent;

/**
 * Creates channel disable events.
 *
 * This factory is responsible for instantiating {@see ChannelDisableEvent} objects with the
 * appropriate channel data, ensuring consistent event creation for channel disable actions.
 */
class ChannelDisableEventFactory implements ChannelActionEventFactoryInterface
{
    #[\Override]
    public function create(Channel $channel)
    {
        return new ChannelDisableEvent($channel);
    }
}
