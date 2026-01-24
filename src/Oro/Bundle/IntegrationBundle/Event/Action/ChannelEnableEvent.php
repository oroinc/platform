<?php

namespace Oro\Bundle\IntegrationBundle\Event\Action;

/**
 * Event dispatched when a channel is being enabled.
 *
 * This event is triggered before a channel enable action is executed, allowing listeners
 * to validate the channel configuration, initialize resources, or prevent the enabling
 * by adding errors.
 */
class ChannelEnableEvent extends ChannelActionEvent
{
    /** @internal */
    const NAME = 'oro_integration.channel_enable';
}
