<?php

namespace Oro\Bundle\WorkflowBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;

use Psr\Log\LoggerInterface;

use Oro\Bundle\WorkflowBundle\Async\Model\TransitionEventTriggerMessage;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class TransitionEventTriggerProcessor implements MessageProcessorInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var WorkflowManager */
    protected $manager;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ManagerRegistry $registry
     * @param WorkflowManager $manager
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $registry, WorkflowManager $manager, LoggerInterface $logger)
    {
        $this->registry = $registry;
        $this->manager = $manager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $triggerMessage = TransitionEventTriggerMessage::createFromJson($message->getBody());

            $trigger = $this->resolveEntity(TransitionEventTrigger::class, $triggerMessage->getTriggerId());

            $workflowItem = $this->resolveEntity(WorkflowItem::class, $triggerMessage->getWorkflowItemId());

            $result = $this->manager->transitIfAllowed($workflowItem, $trigger->getTransitionName());

            if (!$result) {
                throw new \RuntimeException('Transition not allowed');
            }

            return self::ACK;
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    'Message could not be processed: %s. Original message: "%s"',
                    $e->getMessage(),
                    $message->getBody()
                )
            );

            return self::REJECT;
        }
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     * @throws \InvalidArgumentException
     */
    protected function resolveEntity($className, $id)
    {
        if (empty($className) || (int)$id < 1) {
            return null;
        }

        $entity = $this->registry->getManagerForClass($className)->find($className, $id);

        if (!$entity) {
            throw new \RuntimeException(
                sprintf('Entity %s with identifier %s not found.', $className, $id)
            );
        }

        return $entity;
    }
}
