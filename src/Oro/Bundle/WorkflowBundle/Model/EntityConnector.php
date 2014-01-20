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
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

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
     * @param WorkflowItem $workflowItem
     * @param object $entity
     * @throws WorkflowException
     */
    public function setEntity(WorkflowItem $workflowItem, $entity)
    {
        $workflowItem->setEntity($entity);
        $workflowItem->setEntityId($this->getSingleEntityIdentifier($entity));
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

    /**
     * @param object $entity
     * @return integer
     * @throws WorkflowException
     */
    protected function getSingleEntityIdentifier($entity)
    {
        $entityIdentifier = $this->doctrineHelper->getEntityIdentifier($entity);
        if (count($entityIdentifier) != 1) {
            throw new WorkflowException('Can\'t get single identifier for the entity');
        }

        return current($entityIdentifier);
    }
}
