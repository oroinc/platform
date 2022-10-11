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
use Psr\Log\NullLogger;

/**
 * Processes the workflow transition trigger.
 */
class TransitionTriggerProcessor implements MessageProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var TransitionTriggerHandlerInterface */
    protected $handler;

    public function __construct(ManagerRegistry $registry, TransitionTriggerHandlerInterface $handler)
    {
        $this->registry = $registry;
        $this->handler = $handler;
        $this->logger = new NullLogger();
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
}
