<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;

class WorkflowItemListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var WorkflowManagerRegistry */
    protected $workflowManagerRegistry;

    /** @var WorkflowEntityConnector */
    protected $entityConnector;

    /** @var array */
    protected $entitiesScheduledForWorkflowStart = [];

    /** @var int */
    protected $deepLevel = 0;

    /** @var array */
    protected $workflowRelatedClasses;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param WorkflowManagerRegistry $workflowManagerRegistry
     * @param WorkflowEntityConnector $entityConnector
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        WorkflowManagerRegistry $workflowManagerRegistry,
        WorkflowEntityConnector $entityConnector
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->workflowManagerRegistry = $workflowManagerRegistry;
        $this->entityConnector = $entityConnector;
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

        foreach ($this->getApplicableWorkflowsForStart($entity) as $activeWorkflow) {
            if ($activeWorkflow->getStepManager()->hasStartStep()) {
                $this->entitiesScheduledForWorkflowStart[$this->deepLevel][] = new WorkflowStartArguments(
                    $activeWorkflow->getName(),
                    $entity
                );
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

            if (null === $entityId) {
                throw new WorkflowException(
                    sprintf(
                        'Workflow "%s" can not be started because ID of related entity is null',
                        $workflowItem->getWorkflowName()
                    )
                );
            }

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
            $this->getWorkflowManager()->massStartWorkflow($massStartData);
            $this->deepLevel--;
        }

        $this->workflowRelatedClasses = null;
    }

    /**
     * Remove related workflow items
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$this->entityConnector->isApplicableEntity($entity) || !$this->hasWorkflows($entity)) {
            return;
        }

        $workflowItems = $this->getWorkflowManager()->getWorkflowItemsByEntity($entity);

        if ($workflowItems) {
            $em = $args->getEntityManager();
            foreach ($workflowItems as $workflowItem) {
                $em->remove($workflowItem);
            }
        }
    }

    /**
     * @param object $entity
     * @return array|Workflow[]
     */
    protected function getApplicableWorkflowsForStart($entity)
    {
        $applicableWorkflows = $this->getWorkflowManager(false)->getApplicableWorkflows($entity);

        // apply force autostart (ignore default filters)
        $workflows = $this->getWorkflowManager()->getApplicableWorkflows($entity);
        foreach ($workflows as $name => $workflow) {
            if (!$workflow->getDefinition()->isForceAutostart()) {
                continue;
            }
            $applicableWorkflows[$name] = $workflow;
        }

        return $applicableWorkflows;
    }

    /**
     * @param bool $system
     * @return WorkflowManager
     */
    protected function getWorkflowManager($system = true)
    {
        return $this->workflowManagerRegistry->getManager($system ? 'system' : null);
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function hasWorkflows($entity)
    {
        if ($this->workflowRelatedClasses === null) {
            /** @var WorkflowDefinitionRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepository(WorkflowDefinition::class);

            $this->workflowRelatedClasses = $repository->getAllRelatedEntityClasses();
        }

        return in_array($this->doctrineHelper->getEntityClass($entity), $this->workflowRelatedClasses, true);
    }
}
