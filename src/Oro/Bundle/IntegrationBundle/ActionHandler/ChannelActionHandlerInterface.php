<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Defines the contract for handling channel-specific actions.
 *
 * Implementations of this interface are responsible for executing specific actions
 * on integration channels, such as enabling, disabling, or deleting channels.
 * Each handler processes a channel and returns a boolean indicating success or failure.
 */
interface ChannelActionHandlerInterface
{
    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function handleAction(Channel $channel);
}
