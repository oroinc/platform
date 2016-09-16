<?php

namespace Oro\Bundle\WorkflowBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;

use Psr\Log\LoggerInterface;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;
use Oro\Bundle\WorkflowBundle\Helper\TransitionTriggerEventHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class TransitionTriggerEventProcessor implements MessageProcessorInterface
{
    const TRANSITION_TRIGGER_EVENT = 'transitionTriggerEvent';
    const ENTITY_CLASS = 'entityClass';
    const ENTITY_ID = 'entityId';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var TransitionTriggerEventHelper */
    protected $helper;

    /** @var WorkflowManager */
    protected $manager;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ManagerRegistry $registry
     * @param TransitionTriggerEventHelper $helper
     * @param WorkflowManager $manager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ManagerRegistry $registry,
        TransitionTriggerEventHelper $helper,
        WorkflowManager $manager,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->helper = $helper;
        $this->manager = $manager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $messageData = JSON::decode($message->getBody());
            if (!is_array($messageData) || !$messageData) {
                throw new \InvalidArgumentException('Message should not be empty');
            }

            $trigger = $this->getTransitionTriggerEvent($messageData);
            if (!$trigger) {
                throw new \InvalidArgumentException('Message should contain valid TransitionTriggerEvent id');
            }

            $entity = $this->getEntityObject($messageData);
            if (!$entity) {
                throw new \InvalidArgumentException('Message should contain valid entity class name and id');
            }

            if (!$this->helper->isRequirePass($trigger, $entity)) {
                throw new \RuntimeException('Require of TransitionTriggerEvent was not pass');
            }

            $workflowItem = $this->manager->getWorkflowItem($entity, $trigger->getWorkflowDefinition()->getName());
            if (!$workflowItem) {
                throw new \RuntimeException('Could not find WorkflowItem');
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
     * @param array $data
     * @return null|TransitionTriggerEvent
     */
    protected function getTransitionTriggerEvent(array $data)
    {
        if (!array_key_exists(self::TRANSITION_TRIGGER_EVENT, $data)) {
            return null;
        }

        $id = (int)$data[self::TRANSITION_TRIGGER_EVENT];
        if ($id < 1) {
            return null;
        }

        return $this->getEntity(TransitionTriggerEvent::class, $id);
    }

    /**
     * @param array $data
     * @return null|object
     */
    protected function getEntityObject(array $data)
    {
        if (!array_key_exists(self::ENTITY_CLASS, $data) || !array_key_exists(self::ENTITY_ID, $data)) {
            return null;
        }

        return $this->getEntity($data[self::ENTITY_CLASS], $data[self::ENTITY_ID]);
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
