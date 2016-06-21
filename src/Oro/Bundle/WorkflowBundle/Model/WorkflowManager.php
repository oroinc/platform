<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

class WorkflowManager
{
    /**
     * @var WorkflowRegistry
     */
    protected $workflowRegistry;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var WorkflowSystemConfigManager
     */
    protected $config;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param DoctrineHelper $doctrineHelper
     * @param WorkflowSystemConfigManager $workflowSystemConfigManager
     */
    public function __construct(
        WorkflowRegistry $workflowRegistry,
        DoctrineHelper $doctrineHelper,
        WorkflowSystemConfigManager $workflowSystemConfigManager
    ) {
        $this->workflowRegistry = $workflowRegistry;
        $this->doctrineHelper = $doctrineHelper;
        $this->config = $workflowSystemConfigManager;
    }

    /**
     * @param string|Workflow $workflow
     * @return Collection
     */
    public function getStartTransitions($workflow)
    {
        $workflow = $this->getWorkflow($workflow);

        return $workflow->getTransitionManager()->getStartTransitions();
    }

    /**
     * Get workflow instance.
     *
     * string - workflow name
     * WorkflowItem - getWorkflowName() method will be used to get workflow
     * Workflow - will be returned by itself
     *
     * @param string|Workflow|WorkflowItem $workflowIdentifier
     * @throws WorkflowException
     * @return Workflow
     */
    public function getWorkflow($workflowIdentifier)
    {
        if (is_string($workflowIdentifier)) {
            return $this->workflowRegistry->getWorkflow($workflowIdentifier);
        }

        if ($workflowIdentifier instanceof WorkflowItem) {
            return $this->workflowRegistry->getWorkflow($workflowIdentifier->getWorkflowName());
        }

        if ($workflowIdentifier instanceof Workflow) {
            return $workflowIdentifier;
        }

        throw new WorkflowException('Can\'t find workflow by given identifier.');
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return Collection|Transition[]
     */
    public function getTransitionsByWorkflowItem(WorkflowItem $workflowItem)
    {
        $workflow = $this->getWorkflow($workflowItem);

        return $workflow->getTransitionsByWorkflowItem($workflowItem);
    }

    /**
     * @param string|Transition $transition
     * @param WorkflowItem $workflowItem
     * @param Collection $errors
     * @return bool
     */
    public function isTransitionAvailable(WorkflowItem $workflowItem, $transition, Collection $errors = null)
    {
        $workflow = $this->getWorkflow($workflowItem);

        return $workflow->isTransitionAvailable($workflowItem, $transition, $errors);
    }

    /**
     * @param string|Transition $transition
     * @param string|Workflow $workflow
     * @param object $entity
     * @param array $data
     * @param Collection $errors
     * @return bool
     */
    public function isStartTransitionAvailable(
        $workflow,
        $transition,
        $entity,
        array $data = [],
        Collection $errors = null
    ) {
        $workflow = $this->getWorkflow($workflow);

        return $workflow->isStartTransitionAvailable($transition, $entity, $data, $errors);
    }

    /**
     * Perform reset of workflow item data - set $workflowItem and $workflowStep references into null
     * and remove workflow item. If active workflow definition has a start step,
     * then active workflow will be started automatically.
     *
     * @param WorkflowItem $workflowItem
     * @return WorkflowItem|null workflowItem for workflow definition with a start step, null otherwise
     * @throws \Exception
     */
    public function resetWorkflowItem(WorkflowItem $workflowItem)
    {
        $entity = $workflowItem->getEntity();

        return $this->inTransaction(
            function (EntityManager $em) use ($workflowItem, $entity) {
                $currentWorkflowName = $workflowItem->getWorkflowName();

                $em->remove($workflowItem);
                $em->flush();

                $workflow = $this->workflowRegistry->getWorkflow($currentWorkflowName);
                if ($this->isActiveWorkflow($workflow) && $workflow->getStepManager()->hasStartStep()) {
                    return $this->startWorkflow($workflow->getName(), $entity);
                }

                return null;
            },
            WorkflowItem::class
        );
    }

    /**
     * @param callable $callable
     * @param string $entityClass
     * @return mixed
     * @throws \Exception
     */
    private function inTransaction(callable $callable, $entityClass)
    {
        $em = $this->doctrineHelper->getEntityManagerForClass($entityClass);
        $em->beginTransaction();
        try {
            $result = call_user_func($callable, $em);
            $em->commit();

            return $result;
        } catch (\Exception $exception) {
            $em->rollback();
            throw $exception;
        }
    }

    /**
     * @param object|string $entity
     * @return Workflow[]
     */
    public function getApplicableWorkflows($entity)
    {
        return $this->workflowRegistry->getActiveWorkflowsByEntityClass(
            $this->doctrineHelper->getEntityClass($entity)
        );
    }

    /**
     * @param object|string $entity
     * @return bool
     */
    public function hasApplicableWorkflows($entity)
    {
        return count($this->getApplicableWorkflows($entity)) !== 0;
    }

    /**
     * @param string $workflow
     * @param object $entity
     * @param string|Transition|null $transition
     * @param array $data
     * @return WorkflowItem
     * @throws \Exception
     */
    public function startWorkflow($workflow, $entity, $transition = null, array $data = [])
    {
        $workflow = $this->getWorkflow($workflow);

        return $this->inTransaction(
            function (EntityManager $em) use ($workflow, $entity, $transition, &$data) {
                $workflowItem = $workflow->start($entity, $data, $transition);
                $em->persist($workflowItem);
                $em->flush();

                return $workflowItem;
            },
            WorkflowItem::class
        );
    }

    /**
     * Start several workflows in one transaction
     *
     * Input data format:
     * array(
     *      array(
     *          'workflow'   => <workflow identifier: string|Workflow>,
     *          'entity'     => <entity used in workflow: object>,
     *          'transition' => <start transition name: string>,     // optional
     *          'data'       => <additional workflow data : array>,  // optional
     *      ),
     *      ...
     * )
     *
     * @param array $data
     * @throws \Exception
     */
    public function massStartWorkflow(array $data)
    {
        $this->inTransaction(
            function (EntityManager $em) use (&$data) {
                foreach ($data as $row) {
                    if (empty($row['workflow']) || empty($row['entity'])) {
                        continue;
                    }

                    $workflow = $this->getWorkflow($row['workflow']);
                    $entity = $row['entity'];
                    $transition = !empty($row['transition']) ? $row['transition'] : null;
                    $rowData = !empty($row['data']) ? $row['data'] : [];

                    $workflowItem = $workflow->start($entity, $rowData, $transition);
                    $em->persist($workflowItem);
                }

                $em->flush();
            },
            WorkflowItem::class
        );
    }

    /**
     * Perform workflow item transition.
     *
     * @param WorkflowItem $workflowItem
     * @param string|Transition $transition
     * @throws \Exception
     */
    public function transit(WorkflowItem $workflowItem, $transition)
    {
        $workflow = $this->getWorkflow($workflowItem);

        $this->inTransaction(
            function (EntityManager $em) use ($workflow, $workflowItem, $transition) {
                $workflow->transit($workflowItem, $transition);
                $workflowItem->setUpdated(); // transition might not change workflow item
                $em->flush();
            },
            WorkflowItem::class
        );
    }

    /**
     * Transit several workflow items in one transaction
     *
     * Input data format:
     * array(
     *      array(
     *          'workflowItem' => <workflow item entity: WorkflowItem>,
     *          'transition'   => <transition name: string|Transition>
     *      ),
     *      ...
     * )
     *
     * @param array $data
     * @throws \Exception
     */
    public function massTransit(array $data)
    {
        $this->inTransaction(
            function (EntityManager $em) use (&$data) {
                foreach ($data as $row) {
                    if (empty($row['workflowItem']) || !$row['workflowItem'] instanceof WorkflowItem
                        || empty($row['transition'])
                    ) {
                        continue;
                    }

                    /** @var WorkflowItem $workflowItem */
                    $workflowItem = $row['workflowItem'];
                    $workflow = $this->getWorkflow($workflowItem);
                    $transition = $row['transition'];

                    $workflow->transit($workflowItem, $transition);
                    $workflowItem->setUpdated(); // transition might not change workflow item
                }
                $em->flush();
            },
            WorkflowItem::class
        );
    }

    /**
     * @param string $entityClass
     * @return null|Workflow
     * @deprecated use getApplicableWorkflows
     */
    public function getApplicableWorkflowByEntityClass($entityClass)
    {
        throw new \RuntimeException(
            'No single workflow supported for an entity. ' .
            'See \Oro\Bundle\WorkflowBundle\Model\WorkflowManager::getApplicableWorkflowsByEntityClass'
        );
    }

    /**
     * @param string $entityClass
     * @return bool
     * @deprecated use hasApplicableWorkflows
     */
    public function hasApplicableWorkflowsByEntityClass($entityClass)
    {
        return $this->workflowRegistry->hasActiveWorkflowsByEntityClass($entityClass);
    }

    /**
     * @param object $entity
     * @param string $workflowName
     * @return null|WorkflowItem
     */
    public function getWorkflowItem($entity, $workflowName)
    {
        $entityIdentifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        if (false === filter_var($entityIdentifier, FILTER_VALIDATE_INT)) {
            return null;
        }

        return $this->getWorkflowItemRepository()->findOneByEntityMetadata(
            $this->doctrineHelper->getEntityClass($entity),
            $entityIdentifier,
            $workflowName
        );
    }

    /**
     * @return WorkflowItemRepository
     */
    protected function getWorkflowItemRepository()
    {
        return $this->doctrineHelper->getEntityRepository(WorkflowItem::class);
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function hasWorkflowItemsByEntity($entity)
    {
        return count($this->getWorkflowItemsByEntity($entity)) > 0;
    }

    /**
     * @param object $entity
     * @return WorkflowItem[]
     */
    public function getWorkflowItemsByEntity($entity)
    {
        $entityIdentifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        if (false === filter_var($entityIdentifier, FILTER_VALIDATE_INT)) {
            return [];
        }

        $entityClass = $this->doctrineHelper->getEntityClass($entity);

        return $this->getWorkflowItemRepository()->findAllByEntityMetadata($entityClass, $entityIdentifier);
    }

    /**
     * @param string|Workflow|WorkflowItem|WorkflowDefinition $workflowIdentifier
     */
    public function activateWorkflow($workflowIdentifier)
    {
        $definition = $workflowIdentifier instanceof WorkflowDefinition
            ? $workflowIdentifier
            : $this->getWorkflow($workflowIdentifier)->getDefinition();

        $this->config->setWorkflowActive($definition);
    }

    /**
     * @param string|WorkflowDefinition $workflowIdentifier
     */
    public function deactivateWorkflow($workflowIdentifier)
    {
        $definition = $workflowIdentifier instanceof WorkflowDefinition
            ? $workflowIdentifier
            : $this->getWorkflow($workflowIdentifier)->getDefinition();

        $this->config->setWorkflowInactive($definition);
    }

    /**
     * @param string|Workflow|WorkflowItem|WorkflowDefinition $workflowIdentifier
     * @return bool
     */
    public function isActiveWorkflow($workflowIdentifier)
    {
        $definition = $workflowIdentifier instanceof WorkflowDefinition
            ? $workflowIdentifier
            : $this->getWorkflow($workflowIdentifier)->getDefinition();

        return $this->config->isActiveWorkflow($definition);
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     */
    public function resetWorkflowData(WorkflowDefinition $workflowDefinition)
    {
        $this->getWorkflowItemRepository()->resetWorkflowData($workflowDefinition);
    }

    /**
     * Check that entity workflow item is equal to the active workflow item.
     *
     * @param object $entity
     * @param WorkflowItem $currentWorkflowItem
     * @return bool
     */
    public function isResetAllowed($entity, WorkflowItem $currentWorkflowItem)
    {
        $activeWorkflows = $this->getApplicableWorkflows($entity);

        if (!count($activeWorkflows)) {
            return false;
        }

        foreach ($activeWorkflows as $activeWorkflow) {
            if ($activeWorkflow->getName() === $currentWorkflowItem->getWorkflowName()) {
                return false;
            }
        }

        return true;
    }
}
