<?php

namespace Oro\Bundle\IntegrationBundle\Factory\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelActionEvent;

/**
 * Defines the contract for creating {@see ChannelActionEvent} instances.
 *
 * Implementations of this interface are responsible for creating appropriate event instances
 * for different channel actions (enable, disable, delete). This factory pattern allows
 * different event types to be created with consistent initialization logic.
 */
interface ChannelActionEventFactoryInterface
{
    /**
     * @param Channel $channel
     *
     * @return ChannelActionEvent
     */
    public function create(Channel $channel);
}
