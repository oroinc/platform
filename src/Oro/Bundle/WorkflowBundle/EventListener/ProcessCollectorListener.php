<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

class ProcessCollectorListener
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $triggers;

    /**
     * @var array
     */
    protected $scheduledProcesses = array();

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Cache triggers in the internal storage
     */
    protected function initializeTriggers()
    {
        if (null === $this->triggers) {
            $triggers = $this->registry->getRepository('OroWorkflowBundle:ProcessTrigger')->findAllWithDefinitions();
            $this->triggers = array();
            foreach ($triggers as $trigger) {
                $entityClass = $trigger->getDefinition()->getRelatedEntity();
                $event = $trigger->getEvent();
                $field = $trigger->getField();

                if ($event == ProcessTrigger::EVENT_UPDATE) {
                    if ($field) {
                        $this->triggers[$entityClass][$event]['field'][$field][] = $trigger;
                    } else {
                        $this->triggers[$entityClass][$event]['entity'][] = $trigger;
                    }
                } else {
                    $this->triggers[$entityClass][$event][] = $trigger;
                }
            }
        }
    }

    /**
     * @param string $entityClass
     * @param string $event
     * @param string|null $field
     * @return ProcessTrigger[]
     */
    protected function getTriggers($entityClass, $event, $field = null)
    {
        $this->initializeTriggers();

        if ($event == ProcessTrigger::EVENT_UPDATE) {
            if ($field) {
                if (!empty($this->triggers[$entityClass][$event]['field'][$field])) {
                    return $this->triggers[$entityClass][$event]['field'][$field];
                }
            } else {
                if (!empty($this->triggers[$entityClass][$event]['entity'])) {
                    return $this->triggers[$entityClass][$event]['entity'];
                }
            }
        } elseif (!empty($this->triggers[$entityClass][$event])) {
            return $this->triggers[$entityClass][$event];
        }

        return array();
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityClass = $this->getClass($entity);

        $triggers = $this->getTriggers($entityClass, ProcessTrigger::EVENT_CREATE);
        foreach ($triggers as $trigger) {
            $this->scheduleProcess($trigger, $entity);
        }
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityClass = $this->getClass($entity);

        $entityTriggers = $this->getTriggers($entityClass, ProcessTrigger::EVENT_UPDATE);
        foreach ($entityTriggers as $trigger) {
            $this->scheduleProcess($trigger, $entity);
        }

        foreach (array_keys($args->getEntityChangeSet()) as $field) {
            $fieldTriggers = $this->getTriggers($entityClass, ProcessTrigger::EVENT_UPDATE, $field);
            foreach ($fieldTriggers as $trigger) {
                $this->scheduleProcess($trigger, $entity, $args->getOldValue($field), $args->getNewValue($field));
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityClass = $this->getClass($entity);

        $triggers = $this->getTriggers($entityClass, ProcessTrigger::EVENT_DELETE);
        foreach ($triggers as $trigger) {
            // cloned to save all data after flush
            $this->scheduleProcess($trigger, clone $entity);
        }
    }

    /**
     * @param OnClearEventArgs $args
     */
    public function onClear(OnClearEventArgs $args)
    {
        if ($args->clearsAllEntities()
            || $args->getEntityClass() == 'Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger'
        ) {
            $this->triggers = null;
        }

        if ($args->clearsAllEntities()) {
            $this->scheduledProcesses = array();
        } else {
            unset($this->scheduledProcesses[$args->getEntityClass()]);
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        // TODO: Should be implemented in scope of CRM-744
    }

    /**
     * @param ProcessTrigger $trigger
     * @param object $entity
     * @param mixed|null $old
     * @param mixed|null $new
     */
    protected function scheduleProcess(ProcessTrigger $trigger, $entity, $old = null, $new = null)
    {
        $entityClass = $this->getClass($entity);

        $data = new ProcessData(array('entity' => $entity));
        if ($old || $new) {
            $data->set('old', $old)->set('new', $new);
        }

        $this->scheduledProcesses[$entityClass][] = array('trigger' => $trigger, 'data' => $data);
    }

    /**
     * @param object $entity
     * @return string
     */
    protected function getClass($entity)
    {
        return ClassUtils::getClass($entity);
    }
}
