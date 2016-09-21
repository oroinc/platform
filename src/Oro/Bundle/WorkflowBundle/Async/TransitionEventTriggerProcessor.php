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

            $trigger = $this->getEntity(TransitionEventTrigger::class, $triggerMessage->getTriggerId());
            if (!$trigger) {
                throw new \InvalidArgumentException('Message should contain valid TransitionEventTrigger id');
            }

            $workflowItem = $this->getEntity(WorkflowItem::class, $triggerMessage->getWorkflowItemId());
            if (!$workflowItem) {
                throw new \RuntimeException('Message should contain valid WorkflowItem id');
            }

            $result = $this->manager->transitIfAllowed($workflowItem, $trigger->getTransitionName());
            if (!$result) {
                throw new \RuntimeException('Transition not allowed');
            }
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

        return self::ACK;
    }

    /**
     * @param string $className
     * @param int $id
     * @return null|object
     */
    protected function getEntity($className, $id)
    {
        if (empty($className) || (int)$id < 1) {
            return null;
        }

        return $this->registry->getManagerForClass($className)->find($className, $id);
    }
}
