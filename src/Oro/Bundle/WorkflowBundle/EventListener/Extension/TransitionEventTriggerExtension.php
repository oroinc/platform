<?php

namespace Oro\Bundle\WorkflowBundle\EventListener\Extension;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Async\Model\TransitionEventTriggerMessage;
use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\Repository\TransitionEventTriggerRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Helper\TransitionEventTriggerHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class TransitionEventTriggerExtension extends AbstractEventTriggerExtension
{
    const TOPIC_NAME = 'oro_message_queue.transition_trigger_event_message';

    /** @var MessageProducerInterface */
    protected $producer;

    /** @var TransitionEventTriggerHelper */
    protected $helper;

    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var array */
    protected $scheduled = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EventTriggerCache $triggerCache
     * @param MessageProducerInterface $producer
     * @param TransitionEventTriggerHelper $helper
     * @param WorkflowManager $workflowManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EventTriggerCache $triggerCache,
        MessageProducerInterface $producer,
        TransitionEventTriggerHelper $helper,
        WorkflowManager $workflowManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->triggerCache = $triggerCache;
        $this->producer = $producer;
        $this->helper = $helper;
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    public function schedule($entity, $event, array $changeSet = null)
    {
        $entityClass = ClassUtils::getClass($entity);

        /** @var TransitionEventTrigger[] $triggers */
        $triggers = $this->getTriggers($entityClass, $event);
        foreach ($triggers as $trigger) {
            $this->addSchedule($trigger, $entity, $entityClass);
        }

        if ($event === EventTriggerInterface::EVENT_UPDATE) {
            $fields = array_keys($changeSet);
            foreach ($fields as $field) {
                $triggers = $this->getTriggers($entityClass, $event, $field);

                foreach ($triggers as $trigger) {
                    $oldValue = $changeSet[$field]['old'];
                    $newValue = $changeSet[$field]['new'];

                    if (!$this->isEqual($newValue, $oldValue)) {
                        $this->addSchedule($trigger, $entity, $entityClass);
                    }
                }
            }
        }
    }

    /**
     * @param TransitionEventTrigger $trigger
     * @param object $entity
     * @param string $entityClass
     */
    protected function addSchedule(TransitionEventTrigger $trigger, $entity, $entityClass)
    {
        if (!$this->helper->isRequirePass($trigger, $entity)) {
            return;
        }

        $this->scheduled[$entityClass][] = ['trigger' => $trigger, 'entity' => $entity];
    }

    /**
     * {@inheritdoc}
     */
    public function process(ObjectManager $manager)
    {
        foreach ($this->scheduled as $entityClass => $messages) {
            foreach ($messages as $message) {
                /** @var TransitionEventTrigger $trigger */
                $trigger = $message['trigger'];

                $this->processMessage($trigger, $message['entity']);
            }

            $this->clear($entityClass);
        }
    }

    /**
     * @param TransitionEventTrigger $trigger
     * @param object $entity
     */
    protected function processMessage(TransitionEventTrigger $trigger, $entity)
    {
        $mainEntity = $this->helper->getMainEntity($trigger, $entity);
        if (!is_object($mainEntity)) {
            return;
        }

        $workflowItem = $this->workflowManager->getWorkflowItem($mainEntity, $trigger->getWorkflowName());
        if (!$workflowItem) {
            return;
        }

        if ($trigger->isQueued() || $this->forceQueued) {
            $this->producer->send(
                self::TOPIC_NAME,
                TransitionEventTriggerMessage::create($trigger, $workflowItem)->toArray()
            );
        } else {
            $this->workflowManager->transitIfAllowed($workflowItem, $trigger->getTransitionName());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear($entityClass = null)
    {
        parent::clear($entityClass);

        if ($entityClass) {
            unset($this->scheduled[$entityClass]);
        } else {
            $this->scheduled = [];
        }
    }

    /**
     * @return TransitionEventTriggerRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(TransitionEventTrigger::class);
    }
}
