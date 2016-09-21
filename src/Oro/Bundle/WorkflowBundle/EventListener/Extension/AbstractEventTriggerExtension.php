<?php

namespace Oro\Bundle\WorkflowBundle\EventListener\Extension;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;

abstract class AbstractEventTriggerExtension implements EventTriggerExtensionInterface
{
    /** @var EventTriggerCache */
    protected $triggerCache;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var bool */
    protected $forceQueued = false;

    /** @var array */
    protected $triggers;

    /**
     * {@inheritdoc}
     */
    public function setForceQueued($forceQueued = false)
    {
        $this->forceQueued = (bool)$forceQueued;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTriggers($entity, $event)
    {
        return $this->triggerCache->hasTrigger(ClassUtils::getClass($entity), $event);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($entityClass = null)
    {
        $this->triggers = null;
    }

    /**
     * @param string $entityClass
     * @param string $event
     * @param string|null $field
     * @return array|EventTriggerInterface[]
     */
    protected function getTriggers($entityClass, $event, $field = null)
    {
        $this->buildTriggerCache();

        if ($event === EventTriggerInterface::EVENT_UPDATE) {
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

        return [];
    }

    protected function buildTriggerCache()
    {
        if (null !== $this->triggers) {
            return;
        }

        $this->triggers = [];

        /** @var EventTriggerInterface[] $triggers */
        $triggers = $this->getRepository()->findAllWithDefinitions(true);
        foreach ($triggers as $trigger) {
            $entityClass = $trigger->getEntityClass();
            $event = $trigger->getEvent();
            $field = $trigger->getField();

            if ($event === EventTriggerInterface::EVENT_UPDATE) {
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
    /**
     * @param mixed $first
     * @param mixed $second
     * @return bool
     */
    protected function isEqual($first, $second)
    {
        if (is_object($first) && is_object($second) &&
            $this->doctrineHelper->isManageableEntity($first) &&
            $this->doctrineHelper->isManageableEntity($second)
        ) {
            $firstClass = $this->doctrineHelper->getEntityClass($first);
            $secondClass = $this->doctrineHelper->getEntityClass($second);
            $firstIdentifier = $this->doctrineHelper->getEntityIdentifier($first);
            $secondIdentifier = $this->doctrineHelper->getEntityIdentifier($second);

            return $firstClass == $secondClass && $firstIdentifier == $secondIdentifier;
        }

        return $first == $second;
    }


    /**
     * @return ObjectRepository
     */
    abstract protected function getRepository();
}
