<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

class WorkflowManager
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var WorkflowRegistry
     */
    protected $workflowRegistry;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param ManagerRegistry $registry
     * @param WorkflowRegistry $workflowRegistry
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ManagerRegistry $registry,
        WorkflowRegistry $workflowRegistry,
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->registry = $registry;
        $this->workflowRegistry = $workflowRegistry;
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->eventDispatcher = $eventDispatcher;
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
        $activeWorkflowItem = null;
        $entity = $workflowItem->getEntity();

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroWorkflowBundle:WorkflowItem');
        $em->beginTransaction();

        try {
            $currentWorkflowName = $workflowItem->getWorkflowName();
            $this->getWorkflow($workflowItem)->resetWorkflowData($entity);
            $em->remove($workflowItem);
            $em->flush();
            //todo fix in BAP-10808 or BAP-10809
            $activeWorkflows = $this->getApplicableWorkflows($entity);
            foreach ($activeWorkflows as $activeWorkflow) {
                if ($activeWorkflow->getName() === $currentWorkflowName) {
                    if ($activeWorkflow->getStepManager()->hasStartStep()) {
                        $activeWorkflowItem = $this->startWorkflow($activeWorkflow->getName(), $entity);
                    }
                    break;
                }
            }

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        return $activeWorkflowItem;
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

        /** @var EntityManager $em */
        $em = $this->registry->getManager();
        $em->beginTransaction();
        try {
            $workflowItem = $workflow->start($entity, $data, $transition);
            $em->persist($workflowItem);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        return $workflowItem;
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
        /** @var EntityManager $em */
        $em = $this->registry->getManager();
        $em->beginTransaction();
        try {
            foreach ($data as $row) {
                if (empty($row['workflow']) || empty($row['entity'])) {
                    continue;
                }

                $workflow = $this->getWorkflow($row['workflow']);
                $entity = $row['entity'];
                $transition = !empty($row['transition']) ? $row['transition'] : null;
                $data = !empty($row['data']) ? $row['data'] : [];

                $workflowItem = $workflow->start($entity, $data, $transition);
                $em->persist($workflowItem);
            }

            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
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
        /** @var EntityManager $em */
        $em = $this->registry->getManager();
        $em->beginTransaction();
        try {
            $workflow->transit($workflowItem, $transition);
            $workflowItem->setUpdated(); // transition might not change workflow item
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
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
        /** @var EntityManager $em */
        $em = $this->registry->getManager();
        $em->beginTransaction();
        try {
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
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    /**
     * @param object $entity
     * @return Workflow
     */
    public function getApplicableWorkflow($entity)
    {
        return $this->getApplicableWorkflowByEntityClass(
            $this->doctrineHelper->getEntityClass($entity)
        );
    }

    /**
     * @param object $entity
     * @return Workflow[]
     */
    public function getApplicableWorkflows($entity)
    {
        return $this->getApplicableWorkflowsByEntityClass(
            $this->doctrineHelper->getEntityClass($entity)
        );
    }

    /**
     * @param string $entityClass
     * @return null|Workflow
     * @deprecated use getApplicableWorkflowsByEntityClass
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
     * @return null|Workflow
     */
    public function getApplicableWorkflowsByEntityClass($entityClass)
    {
        return $this->workflowRegistry->getActiveWorkflowsByEntityClass($entityClass);
    }

    /**
     * @param string $entityClass
     * @return bool
     * @deprecated
     */
    public function hasApplicableWorkflowByEntityClass($entityClass)
    {
        return $this->workflowRegistry->hasActiveWorkflowByEntityClass($entityClass);
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    public function hasApplicableWorkflowsByEntityClass($entityClass)
    {
        return $this->workflowRegistry->hasActiveWorkflowsByEntityClass($entityClass);
    }

    /**
     * @param object $entity
     * @return WorkflowItem|null
     */
    public function getWorkflowItemByEntity($entity)
    {
        $entityIdentifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        if (false === filter_var($entityIdentifier, FILTER_VALIDATE_INT)) {
            return null;
        }

        $entityClass = $this->doctrineHelper->getEntityClass($entity);

        return $this->getWorkflowItemRepository()->findByEntityMetadata($entityClass, $entityIdentifier);
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
     * @return Workflow[]
     */
    public function getWorkflowItemsByEntity($entity)
    {
        $entityIdentifier = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        if (false === filter_var($entityIdentifier, FILTER_VALIDATE_INT)) {
            return null;
        }

        $entityClass = $this->doctrineHelper->getEntityClass($entity);

        return $this->getWorkflowItemRepository()->findAllByEntityMetadata($entityClass, $entityIdentifier);
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
     * @param string|Workflow|WorkflowItem|WorkflowDefinition $workflowIdentifier
     */
    public function activateWorkflow($workflowIdentifier)
    {
        $definition = $workflowIdentifier instanceof WorkflowDefinition
            ? $workflowIdentifier
            : $this->getWorkflow($workflowIdentifier)->getDefinition();

        $entityClass = $definition->getRelatedEntity();
        $workflowName = $definition->getName();

        $this->setActiveWorkflow($entityClass, $workflowName);

        $this->eventDispatcher->dispatch(WorkflowEvents::WORKFLOW_ACTIVATED, new WorkflowChangesEvent($definition));
    }

    /**
     * @param string|WorkflowDefinition $workflowIdentifier
     */
    public function deactivateWorkflow($workflowIdentifier)
    {
        $definition = $workflowIdentifier instanceof WorkflowDefinition
            ? $workflowIdentifier
            : $this->getWorkflow($workflowIdentifier)->getDefinition();

        $entityConfig = $this->getEntityConfig($definition->getRelatedEntity());
        $entityConfig->set(
            'active_workflows',
            array_diff($entityConfig->get('active_workflows', false, []), [$definition->getName()])
        );
        $this->persistEntityConfig($entityConfig);

        if ($definition) {
            $this->eventDispatcher->dispatch(
                WorkflowEvents::WORKFLOW_DEACTIVATED,
                new WorkflowChangesEvent($definition)
            );
        }
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     */
    public function resetWorkflowData(WorkflowDefinition $workflowDefinition)
    {
        $this->getWorkflowItemRepository()->resetWorkflowData($workflowDefinition);
    }

    /**
     * @param string|Workflow|WorkflowItem|WorkflowDefinition $workflowIdentifier
     */
    public function isActiveWorkflow($workflowIdentifier)
    {
        $definition = $workflowIdentifier instanceof WorkflowDefinition
            ? $workflowIdentifier
            : $this->getWorkflow($workflowIdentifier)->getDefinition();

        $entityConfig = $this->getEntityConfig($definition->getRelatedEntity());
        $activeWorkflows = (array)$entityConfig->get('active_workflow');

        return in_array($definition->getName(), $activeWorkflows, true);
    }

    /**
     * @param string $entityClass
     * @param string|null $workflowName
     */
    protected function setActiveWorkflow($entityClass, $workflowName)
    {
        $entityConfig = $this->getEntityConfig($entityClass);

        $entityConfig->set(
            'active_workflows',
            array_merge($entityConfig->get('active_workflows', false, []), [$workflowName])
        );

        $this->persistEntityConfig($entityConfig);
    }

    /**
     * @param $entityClass
     * @return ConfigInterface
     * @throws WorkflowException
     */
    protected function getEntityConfig($entityClass)
    {
        $workflowConfigProvider = $this->configManager->getProvider('workflow');
        if ($workflowConfigProvider->hasConfig($entityClass)) {
            return $workflowConfigProvider->getConfig($entityClass);
        }

        throw new WorkflowException(sprintf('Entity %s is not configurable', $entityClass));
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

        if ($currentWorkflowItem) {
            foreach ($activeWorkflows as $activeWorkflow) {
                if ($activeWorkflow->getName() === $currentWorkflowItem->getWorkflowName()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param ConfigInterface $entityConfig
     */
    protected function persistEntityConfig(ConfigInterface $entityConfig)
    {
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();
    }

    /**
     * @return WorkflowItemRepository
     */
    protected function getWorkflowItemRepository()
    {
        return $this->registry->getRepository('OroWorkflowBundle:WorkflowItem');
    }
}
