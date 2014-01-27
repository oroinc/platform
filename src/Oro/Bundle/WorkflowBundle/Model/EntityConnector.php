<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

/**
 * Connects related entities with workflow entities
 */
class EntityConnector
{
    const PROPERTY_WORKFLOW_ITEM = 'workflowItem';
    const PROPERTY_WORKFLOW_STEP = 'workflowStep';

    /**
     * @param object $entity
     * @param WorkflowItem $workflowItem
     */
    public function setWorkflowItem($entity, WorkflowItem $workflowItem)
    {
        $this->setProperty($entity, self::PROPERTY_WORKFLOW_ITEM, $workflowItem);
    }

    /**
     * @param object $entity
     * @param WorkflowStep $workflowStep
     */
    public function setWorkflowStep($entity, WorkflowStep $workflowStep)
    {
        $this->setProperty($entity, self::PROPERTY_WORKFLOW_STEP, $workflowStep);
    }

    /**
     * @param object $entity
     * @return WorkflowItem
     */
    public function getWorkflowItem($entity)
    {
        return $this->getProperty($entity, self::PROPERTY_WORKFLOW_ITEM);
    }

    /**
     * @param object $entity
     * @return WorkflowStep
     */
    public function getWorkflowStep($entity)
    {
        return $this->getProperty($entity, self::PROPERTY_WORKFLOW_STEP);
    }

    /**
     * @param object $entity
     * @param string $property
     * @param mixed $value
     * @throws WorkflowException
     */
    protected function setProperty($entity, $property, $value)
    {
        $setter = 'set' . ucfirst($property);
        if (!method_exists($entity, $setter)) {
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
        $getter = 'get' . ucfirst($property);
        if (!method_exists($entity, $getter)) {
            throw new WorkflowException(sprintf('Can\'t get property "%s" from entity', $property));
        }

        return $entity->$getter();
    }
}
