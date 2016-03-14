<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Field\FieldGenerator;

/**
 * Connects related entities with workflow entities
 */
class EntityConnector
{
    /**
     * @param object|WorkflowAwareInterface $entity
     * @param WorkflowItem $workflowItem
     */
    public function setWorkflowItem($entity, WorkflowItem $workflowItem = null)
    {
        if ($entity instanceof WorkflowAwareInterface) {
            $entity->setWorkflowItem($workflowItem);
        } else {
            $this->setProperty($entity, FieldGenerator::PROPERTY_WORKFLOW_ITEM, $workflowItem);
        }
    }

    /**
     * @param object|WorkflowAwareInterface $entity
     * @param WorkflowStep $workflowStep
     */
    public function setWorkflowStep($entity, WorkflowStep $workflowStep = null)
    {
        if ($entity instanceof WorkflowAwareInterface) {
            $entity->setWorkflowStep($workflowStep);
        } else {
            $this->setProperty($entity, FieldGenerator::PROPERTY_WORKFLOW_STEP, $workflowStep);
        }
    }

    /**
     * @param object|WorkflowAwareInterface $entity
     * @return WorkflowItem
     */
    public function getWorkflowItem($entity)
    {
        if ($entity instanceof WorkflowAwareInterface) {
            return $entity->getWorkflowItem();
        }

        return $this->getProperty($entity, FieldGenerator::PROPERTY_WORKFLOW_ITEM);
    }

    /**
     * @param object|WorkflowAwareInterface $entity
     * @return WorkflowStep
     */
    public function getWorkflowStep($entity)
    {
        if ($entity instanceof WorkflowAwareInterface) {
            return $entity->getWorkflowStep();
        }

        return $this->getProperty($entity, FieldGenerator::PROPERTY_WORKFLOW_STEP);
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
        return is_a($entityOrClass, 'Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface', true)
            || ($this->getGetter($entityOrClass, FieldGenerator::PROPERTY_WORKFLOW_ITEM)
                && $this->getSetter($entityOrClass, FieldGenerator::PROPERTY_WORKFLOW_ITEM)
                && $this->getGetter($entityOrClass, FieldGenerator::PROPERTY_WORKFLOW_STEP)
                && $this->getSetter($entityOrClass, FieldGenerator::PROPERTY_WORKFLOW_STEP)
            );
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
