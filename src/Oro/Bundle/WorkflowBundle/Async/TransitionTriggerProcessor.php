<?php

namespace Oro\Bundle\WorkflowBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Handler\TransitionTriggerHandlerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Processes the workflow transition trigger.
 */
class TransitionTriggerProcessor implements MessageProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var LoggerInterface */
    protected $logger;

    /** @var TransitionTriggerHandlerInterface */
    protected $handler;

    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $logger,
        TransitionTriggerHandlerInterface $handler
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->handler = $handler;
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $triggerMessage = TransitionTriggerMessage::createFromArray($message->getBody());
        $trigger = $this->registry
            ->getManagerForClass(BaseTransitionTrigger::class)
            ->find(BaseTransitionTrigger::class, $triggerMessage->getTriggerId());
        if (!$trigger) {
            $this->logger->error(
                'Transition trigger #{id} is not found',
                ['id' => $triggerMessage->getTriggerId()]
            );

            return self::REJECT;
        }

        if (!$this->handler->process($trigger, $triggerMessage)) {
            $this->logger->warning(
                'Transition not allowed',
                ['trigger' => $trigger]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * @param MessageInterface $message
     * @return TransitionTriggerMessage
     * @throws \InvalidArgumentException
     */
    protected function createTransitionTriggerMessage(MessageInterface $message)
    {
        return TransitionTriggerMessage::createFromJson($message->getBody());
    }

    /**
     * @param int $id
     * @return BaseTransitionTrigger|null
     * @throws \InvalidArgumentException|EntityNotFoundException
     */
    protected function resolveTrigger($id)
    {
        if ((int)$id < 1) {
            throw new \InvalidArgumentException('Message should contain valid transition trigger id');
        }

        $entity = $this->registry->getManagerForClass(BaseTransitionTrigger::class)
            ->find(BaseTransitionTrigger::class, $id);

        if (!$entity) {
            throw new EntityNotFoundException(sprintf('Transition trigger entity with identifier %s not found', $id));
        }

        return $entity;
    }
}
