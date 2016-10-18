<?php

namespace Oro\Bundle\WorkflowBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityNotFoundException;

use Psr\Log\LoggerInterface;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Handler\TransitionTriggerHandlerInterface;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class TransitionTriggerProcessor implements MessageProcessorInterface
{
    const CRON_TOPIC_NAME = 'oro_message_queue.transition_trigger_cron_message';
    const EVENT_TOPIC_NAME = 'oro_message_queue.transition_trigger_event_message';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var LoggerInterface */
    protected $logger;

    /** @var TransitionTriggerHandlerInterface */
    protected $handler;

    /**
     * @param ManagerRegistry $registry
     * @param LoggerInterface $logger
     * @param TransitionTriggerHandlerInterface $handler
     */
    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $logger,
        TransitionTriggerHandlerInterface $handler
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $triggerMessage = $this->createTransitionTriggerMessage($message);
            $trigger = $this->resolveTrigger($triggerMessage->getTriggerId());

            if (!$this->handler->process($trigger, $triggerMessage)) {
                throw new \RuntimeException('Transition not allowed');
            }

            return self::ACK;
        } catch (\Exception $e) {
            $this->logger->error(
                'Message could not be processed.',
                [
                    'exception' => $e,
                    'originalMessage' => $message->getBody(),
                ]
            );

            return self::REJECT;
        }
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
