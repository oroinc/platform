<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\DeleteManager;

/**
 * Handles the deletion of integration channels.
 *
 * This handler is responsible for removing integration channels from the system.
 * It delegates the actual deletion logic to the {@see DeleteManager}, which handles
 * the removal of the channel and any related data.
 */
class ChannelDeleteActionHandler implements ChannelActionHandlerInterface
{
    /**
     * @var DeleteManager
     */
    private $deleteManager;

    public function __construct(DeleteManager $deleteManager)
    {
        $this->deleteManager = $deleteManager;
    }

    #[\Override]
    public function handleAction(Channel $channel)
    {
        return $this->deleteManager->delete($channel);
    }
}
