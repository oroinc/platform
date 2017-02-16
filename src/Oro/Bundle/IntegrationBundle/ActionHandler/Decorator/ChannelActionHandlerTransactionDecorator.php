<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler\Decorator;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\ActionHandler\ChannelActionHandlerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ChannelActionHandlerTransactionDecorator implements ChannelActionHandlerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ChannelActionHandlerInterface
     */
    private $actionHandler;

    /**
     * @param EntityManagerInterface        $entityManager
     * @param ChannelActionHandlerInterface $actionHandler
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ChannelActionHandlerInterface $actionHandler
    ) {
        $this->entityManager = $entityManager;
        $this->actionHandler = $actionHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function handleAction(Channel $channel)
    {
        $this->entityManager->beginTransaction();

        if (!$this->actionHandler->handleAction($channel)) {
            $this->entityManager->rollback();

            return false;
        }

        $this->entityManager->commit();

        return true;
    }
}
