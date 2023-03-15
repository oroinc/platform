<?php

namespace Oro\Bundle\WorkflowBundle\EventListener\Extension;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\WorkflowBundle\Async\Topic\WorkflowTransitionEventTriggerTopic;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\Repository\TransitionEventTriggerRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Handler\TransitionEventTriggerHandler;
use Oro\Bundle\WorkflowBundle\Helper\TransitionEventTriggerHelper;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Extension for transition event triggers.
 */
class TransitionEventTriggerExtension extends AbstractEventTriggerExtension
{
    /** @var MessageProducerInterface */
    protected $producer;

    /** @var TransitionEventTriggerHelper */
    protected $helper;

    /** @var TransitionEventTriggerHandler */
    protected $handler;

    /** @var array */
    protected $scheduled = [];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EventTriggerCache $triggerCache,
        MessageProducerInterface $producer,
        TransitionEventTriggerHelper $helper,
        TransitionEventTriggerHandler $handler
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->triggerCache = $triggerCache;
        $this->producer = $producer;
        $this->helper = $helper;
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function schedule($entity, $event, array $changeSet = null)
    {
        $entityClass = ClassUtils::getClass($entity);

        /** @var TransitionEventTrigger[] $triggers */
        $triggers = $this->getTriggers($entityClass, $event);
        $previousEntity = $this->createEntityFromChangeSet($entity, $changeSet);
        foreach ($triggers as $trigger) {
            $this->addSchedule($trigger, $entity, $entityClass, $previousEntity);
        }

        if ($event === EventTriggerInterface::EVENT_UPDATE) {
            $fields = array_keys($changeSet);
            foreach ($fields as $field) {
                $triggers = $this->getTriggers($entityClass, $event, $field);

                foreach ($triggers as $trigger) {
                    $oldValue = $changeSet[$field]['old'];
                    $newValue = $changeSet[$field]['new'];

                    if (!$this->isEqual($newValue, $oldValue)) {
                        $this->addSchedule($trigger, $entity, $entityClass, $previousEntity);
                    }
                }
            }
        }
    }

    /**
     * @param TransitionEventTrigger $trigger
     * @param object $entity
     * @param string $entityClass
     * @param object $prevEntity
     */
    protected function addSchedule(TransitionEventTrigger $trigger, $entity, $entityClass, $prevEntity)
    {
        if (!$this->helper->isRequirePass($trigger, $entity, $prevEntity)) {
            return;
        }

        $this->scheduled[$entityClass][] = ['trigger' => $trigger, 'entity' => $entity];
    }

    /**
     * {@inheritdoc}
     */
    public function process(ObjectManager $manager)
    {
        foreach ($this->scheduled as &$messages) {
            while ($message = array_shift($messages)) {
                /** @var TransitionEventTrigger $trigger */
                $trigger = $message['trigger'];

                $this->processMessage($trigger, $message['entity']);
            }
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

        $entityId = $this->doctrineHelper->getEntityIdentifier($mainEntity);
        $message = TransitionTriggerMessage::create($trigger, $entityId);

        if ($trigger->isQueued() || $this->forceQueued) {
            $this->producer->send(WorkflowTransitionEventTriggerTopic::getName(), $message->toArray());
        } else {
            $this->handler->process($trigger, $message);
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

    /**
     * @param object $entity
     * @param array $changeSet
     *
     * @return object
     */
    private function createEntityFromChangeSet($entity, array $changeSet = null)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $newEntity = clone $entity;

        if (null === $changeSet) {
            return $newEntity;
        }

        foreach ($changeSet as $field => $value) {
            if ($accessor->isWritable($newEntity, $field)) {
                $accessor->setValue($newEntity, $field, $value['old']);
            }
        }

        return $newEntity;
    }
}
