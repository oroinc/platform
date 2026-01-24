<?php

namespace Oro\Bundle\IntegrationBundle\ActionHandler\Decorator;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\ActionHandler\ChannelActionHandlerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Wraps channel action handler execution in a database transaction.
 *
 * This decorator ensures that channel actions are executed within a database transaction,
 * providing atomicity guarantees. If the wrapped action handler fails, the transaction is
 * rolled back; otherwise, it is committed. This prevents partial updates to the database
 * when channel actions fail.
 */
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

    public function __construct(
        EntityManagerInterface $entityManager,
        ChannelActionHandlerInterface $actionHandler
    ) {
        $this->entityManager = $entityManager;
        $this->actionHandler = $actionHandler;
    }

    #[\Override]
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
