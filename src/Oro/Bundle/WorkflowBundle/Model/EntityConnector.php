<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

/**
 * Connects related entities with workflow entities
 */
class EntityConnector
{
    /**
     * @param object|WorkflowAwareInterface $entity
     * @param WorkflowItem $workflowItem
     *
     * @throws WorkflowException
     */
    public function setWorkflowItem($entity, WorkflowItem $workflowItem = null)
    {
        if ($entity instanceof WorkflowAwareInterface) {
            $entity->setWorkflowItem($workflowItem);
        } else {
            throw new WorkflowException('Can\'t set property "workflowItem" to entity');
        }
    }

    /**
     * @param object|WorkflowAwareInterface $entity
     * @param WorkflowStep $workflowStep
     *
     * @throws WorkflowException
     */
    public function setWorkflowStep($entity, WorkflowStep $workflowStep = null)
    {
        if ($entity instanceof WorkflowAwareInterface) {
            $entity->setWorkflowStep($workflowStep);
        } else {
            throw new WorkflowException('Can\'t set property "workflowStep" to entity');
        }
    }

    /**
     * @param object|WorkflowAwareInterface $entity
     *
     * @throws WorkflowException
     *
     * @return WorkflowItem
     */
    public function getWorkflowItem($entity)
    {
        if ($entity instanceof WorkflowAwareInterface) {
            return $entity->getWorkflowItem();
        }

        throw new WorkflowException('Can\'t get property "workflowItem" from entity');
    }

    /**
     * @param object|WorkflowAwareInterface $entity
     *
     * @throws WorkflowException
     *
     * @return WorkflowStep
     */
    public function getWorkflowStep($entity)
    {
        if ($entity instanceof WorkflowAwareInterface) {
            return $entity->getWorkflowStep();
        }

        throw new WorkflowException('Can\'t get property "workflowStep" from entity');
    }

    /**
     * @param object $entity
     * @param string $property
     * @param mixed $value
     * @throws WorkflowException
     */
    protected function setProperty($entity, $property, $value = null)
    {
        $setter = $this->getSetter($entity, $property);
        if (!$setter) {
            throw new WorkflowException(sprintf('Can\'t set property "%s" to entity', $property));
        }

        $entity->$setter($value);
    }

    /**
     * @param object $entity
     * @param string $property
     * @return mixed
     * @throws WorkflowException
     */
    protected function getProperty($entity, $property)
    {
        $getter = $this->getGetter($entity, $property);
        if (!$getter) {
            throw new WorkflowException(sprintf('Can\'t get property "%s" from entity', $property));
        }

        return $entity->$getter();
    }

    /**
     * @param object|string $entityOrClass
     * @param string $property
     * @return null|string
     */
    protected function getSetter($entityOrClass, $property)
    {
        $setter = 'set' . ucfirst($property);
        return method_exists($entityOrClass, $setter) ? $setter : null;
    }

    /**
     * @param object|string $entityOrClass
     * @param string $property
     * @return null|string
     */
    protected function getGetter($entityOrClass, $property)
    {
        $getter = 'get' . ucfirst($property);
        return method_exists($entityOrClass, $getter) ? $getter : null;
    }

    /**
     * @param object|string $entityOrClass
     * @return bool
     */
    public function isWorkflowAware($entityOrClass)
    {
        return is_a($entityOrClass, 'Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface', true);
    }

    /**
     * @param object $entity
     */
    public function resetWorkflowData($entity)
    {
        $this->setWorkflowItem($entity, null);
        $this->setWorkflowStep($entity, null);
    }
}
