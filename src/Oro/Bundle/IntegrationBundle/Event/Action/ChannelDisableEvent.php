<?php

namespace Oro\Bundle\IntegrationBundle\Event\Action;

/**
 * Event dispatched when a channel is being disabled.
 *
 * This event is triggered before a channel disable action is executed, allowing listeners
 * to perform cleanup operations, stop synchronization processes, or prevent the disabling
 * by adding errors.
 */
class ChannelDisableEvent extends ChannelActionEvent
{
    /** @internal */
    public const NAME = 'oro_integration.channel_disable';
}
