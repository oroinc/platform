<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\DeleteManager;

class ChannelDeleteActionHandler implements ChannelActionHandlerInterface
{
    /**
     * @var DeleteManager
     */
    private $deleteManager;

    /**
     * @param DeleteManager $deleteManager
     */
    public function __construct(DeleteManager $deleteManager)
    {
        $this->deleteManager = $deleteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handleAction(Channel $channel)
    {
        return $this->deleteManager->delete($channel);
    }
}
