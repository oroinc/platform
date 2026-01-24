<?php

namespace Oro\Bundle\IntegrationBundle\Event\Action;

/**
 * Event dispatched when a channel is being deleted.
 *
 * This event is triggered before a channel deletion action is executed, allowing listeners
 * to validate the deletion, perform cleanup operations, or prevent the deletion by adding errors.
 */
class ChannelDeleteEvent extends ChannelActionEvent
{
    /** @internal */
    const NAME = 'oro_integration.channel_delete';
}
