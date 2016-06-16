<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowItemListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EntityConnector
     */
    protected $entityConnector;

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var array
     */
    protected $entitiesScheduledForWorkflowStart = array();

    /**
     * @var int
     */
    protected $deepLevel = 0;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityConnector $entityConnector
     * @param WorkflowManager $workflowManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityConnector $entityConnector,
        WorkflowManager $workflowManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityConnector = $entityConnector;
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->updateWorkflowItemEntityRelation($args);
        $this->scheduleStartWorkflowForNewEntity($args);
    }

    /**
     * Schedule workflow auto start for entity.
     *
     * @param LifecycleEventArgs $args
     */
    protected function scheduleStartWorkflowForNewEntity(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $activeWorkflows = $this->workflowManager->getApplicableWorkflows($entity);
    
        if($activeWorkflows) {
            foreach ($activeWorkflows as $activeWorkflow) {
                if ($activeWorkflow->getStepManager()->hasStartStep()) {
                    $this->entitiesScheduledForWorkflowStart[$this->deepLevel][] = array(
                        'entity' => $entity,
                        'workflow' => $activeWorkflow
                    );
                }
            }
        }
    }

    /**
     * Set workflow item entity ID
     *
     * @param LifecycleEventArgs $args
     * @throws WorkflowException
     */
    protected function updateWorkflowItemEntityRelation(LifecycleEventArgs $args)
    {
        $workflowItem = $args->getEntity();
        if ($workflowItem instanceof WorkflowItem && !$workflowItem->getEntityId()) {
            $entity = $workflowItem->getEntity();
            if (!$entity) {
                throw new WorkflowException('Workflow item does not contain related entity');
            }
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            $workflowItem->setEntityId($entityId);

            $entityClass = $this->doctrineHelper->getEntityClass($entity);
            $workflowItem->setEntityClass($entityClass);

            $unitOfWork = $args->getEntityManager()->getUnitOfWork();
            $unitOfWork->scheduleExtraUpdate(
                $workflowItem,
                [
                    'entityId' => [null, $entityId],
                    'entityClass' => [null, $entityClass]
                ]
            );
        }
    }

    /**
     * Execute workflow start for scheduled entities.
     */
    public function postFlush()
    {
        $currentDeepLevel = $this->deepLevel;

        if (!empty($this->entitiesScheduledForWorkflowStart[$currentDeepLevel])) {
            $this->deepLevel++;
            $massStartData = $this->entitiesScheduledForWorkflowStart[$currentDeepLevel];
            unset($this->entitiesScheduledForWorkflowStart[$currentDeepLevel]);
            $this->workflowManager->massStartWorkflow($massStartData);
            $this->deepLevel--;
        }
    }

    /**
     * Remove related workflow item
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($this->entityConnector->isWorkflowAware($entity)) {
            $workflowItem = $this->entityConnector->getWorkflowItem($entity);
            if ($workflowItem) {
                $args->getEntityManager()->remove($workflowItem);
            }
        }
    }
}
