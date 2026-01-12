<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Handles the disabling of integration channels.
 *
 * This handler is responsible for deactivating integration channels while preserving
 * their configuration. It records the previous enabled state before disabling the channel,
 * allowing for potential restoration of the channel's state if needed.
 */
class ChannelDisableActionHandler implements ChannelActionHandlerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[\Override]
    public function handleAction(Channel $channel)
    {
        $channel->setPreviouslyEnabled($channel->isEnabled());
        $channel->setEnabled(false);

        $this->entityManager->flush();

        return true;
    }
}
