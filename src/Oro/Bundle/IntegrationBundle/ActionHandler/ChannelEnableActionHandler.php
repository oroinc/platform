<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Handles the enabling of integration channels.
 *
 * This handler is responsible for activating integration channels. It records the previous
 * enabled state before enabling the channel, allowing for potential restoration of the
 * channel's state if needed.
 */
class ChannelEnableActionHandler implements ChannelActionHandlerInterface
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
        $channel->setEnabled(true);

        $this->entityManager->flush();

        return true;
    }
}
