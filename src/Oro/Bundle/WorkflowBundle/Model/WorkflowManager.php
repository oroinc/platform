<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowRecordGroupException;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var WorkflowEntityConnector */
    private $entityConnector;

    /** @var WorkflowRegistry */
    private $workflowRegistry;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     * @param WorkflowEntityConnector $entityConnector
     */
    public function __construct(
        WorkflowRegistry $workflowRegistry,
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher,
        WorkflowEntityConnector $entityConnector
    ) {
        $this->workflowRegistry = $workflowRegistry;
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityConnector = $entityConnector;
    }

    /**
     * @param string|Workflow|WorkflowItem $workflowIdentifier
     * @return Workflow
     * @throws WorkflowException
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
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());

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
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());

        return $workflow->isTransitionAvailable($workflowItem, $transition, $errors);
    }

    /**
     * @param string|Workflow $workflow
     * @param string|Transition $transition
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
        //consider to refactor (e.g. remove) type check in favor of string usage only as most cases are
        //any way. if developer has Workflow instance already it is possible to get decision from it directly as below
        $workflow = $workflow instanceof Workflow ? $workflow : $this->workflowRegistry->getWorkflow($workflow);

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
                if ($workflow->isActive() && $workflow->getStepManager()->hasStartStep()) {
                    return $this->startWorkflow($currentWorkflowName, $entity);
                }

                return null;
            },
            WorkflowItem::class
        );
    }

    /**
     * @param string $workflow
     * @param object $entity
     * @param string|Transition|null $transition
     * @param array $data
     * @param bool $throwGroupException
     * @return WorkflowItem
     * @throws \Exception
     * @throws WorkflowRecordGroupException
     */
    public function startWorkflow($workflow, $entity, $transition = null, array $data = [], $throwGroupException = true)
    {
        //consider to refactor (e.g. remove) type check in favor of string usage only as most cases are
        $workflow = $workflow instanceof Workflow ? $workflow : $this->workflowRegistry->getWorkflow($workflow);
        if (!$this->isStartAllowedByRecordGroups($entity, $workflow->getDefinition()->getExclusiveRecordGroups())) {
            if ($throwGroupException) {
                throw new WorkflowRecordGroupException(
                    sprintf(
                        'Workflow "%s" can not be started because it belongs to exclusive_record_group ' .
                        'with already started other workflow for this entity',
                        [$workflow->getName()]
                    )
                );
            }

            return null;
        }

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
     * @param array|WorkflowStartArguments[] $startArgumentsList instances of WorkflowStartArguments
     */
    public function massStartWorkflow(array $startArgumentsList)
    {
        $this->inTransaction(
            function (EntityManager $em) use (&$startArgumentsList) {
                foreach ($startArgumentsList as $startArguments) {
                    if (!$startArguments instanceof WorkflowStartArguments) {
                        continue;
                    }

                    $this->startWorkflow(
                        $startArguments->getWorkflowName(),
                        $startArguments->getEntity(),
                        $startArguments->getTransition(),
                        $startArguments->getData(),
                        false
                    );
                }
            },
            WorkflowItem::class
        );
    }

    /**
     * Perform workflow item transition.
     *
     * @param WorkflowItem $workflowItem
     * @param string|Transition $transition
     */
    public function transit(WorkflowItem $workflowItem, $transition)
    {
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());

        $this->transitWorkflow($workflow, $workflowItem, $transition);
    }

    /**
     * @param Workflow $workflow
     * @param WorkflowItem $workflowItem
     * @param $transition
     */
    private function transitWorkflow(Workflow $workflow, WorkflowItem $workflowItem, $transition)
    {
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
     * Tries to transit workflow and checks weather given transition is allowed.
     * Returns true on success - false otherwise.
     * @param WorkflowItem $workflowItem
     * @param $transition
     * @return bool
     */
    public function transitIfAllowed(WorkflowItem $workflowItem, $transition)
    {
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());

        if (!$workflow->isTransitionAllowed($workflowItem, $transition)) {
            return false;
        }

        $this->transitWorkflow($workflow, $workflowItem, $transition);

        return true;
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
                    $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());
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
     * @throws \InvalidArgumentException
     */
    public function getApplicableWorkflows($entity)
    {
        if (!is_object($entity)) {
            throw new \InvalidArgumentException(
                'Instance of entity object is required to getApplicableWorkflows as an argument.'
            );
        }

        $existingRecordsInGroups = [];

        foreach ($this->getWorkflowItemsByEntity($entity) as $workflowItem) {
            $groups = $workflowItem->getDefinition()->getExclusiveRecordGroups(); //todo think about getDefinition fetch
            if (count($groups) !== 0) {
                $name = $workflowItem->getWorkflowName();
                foreach ($groups as $group) {
                    $existingRecordsInGroups[$group] = $name;
                }
            }
        }

        return $this->workflowRegistry
            ->getActiveWorkflowsByEntityClass($this->doctrineHelper->getEntityClass($entity))
            ->filter(
                function (Workflow $workflow) use (&$existingRecordsInGroups) {
                    $workflowRecordGroups = $workflow->getDefinition()->getExclusiveRecordGroups();
                    foreach ($workflowRecordGroups as $workflowRecordGroup) {
                        if (!array_key_exists($workflowRecordGroup, $existingRecordsInGroups)) {
                            continue;
                        }

                        if ($existingRecordsInGroups[$workflowRecordGroup] !== $workflow->getName()) {
                            return false;
                        }
                    }

                    return true;
                }
            )
            ->toArray();
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
     * @param object $entity
     * @param string $workflowName
     * @return null|WorkflowItem
     */
    public function getWorkflowItem($entity, $workflowName)
    {
        if (!$this->entityConnector->isApplicableEntity($entity)) {
            return null;
        }

        return $this->getWorkflowItemRepository()->findOneByEntityMetadata(
            $this->doctrineHelper->getEntityClass($entity),
            $this->doctrineHelper->getSingleEntityIdentifier($entity),
            $workflowName
        );
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
        if (!$this->entityConnector->isApplicableEntity($entity)) {
            return [];
        }

        return $this->getWorkflowItemRepository()->findAllByEntityMetadata(
            $this->doctrineHelper->getEntityClass($entity),
            $this->doctrineHelper->getSingleEntityIdentifier($entity)
        );
    }

    /**
     * Ensures that workflow is currently active
     * @param string $workflowName
     * @return bool weather workflow was changed his state
     */
    public function activateWorkflow($workflowName)
    {
        return $this->setWorkflowStatus($workflowName, true);
    }

    /**
     * Ensures that workflow is currently inactive
     * @param string $workflowName
     * @return bool weather workflow was changed his state
     */
    public function deactivateWorkflow($workflowName)
    {
        return $this->setWorkflowStatus($workflowName, false);
    }

    /**
     * @param string $workflowName
     * @param bool $isActive
     * @return bool weather workflow was changed his state
     */
    private function setWorkflowStatus($workflowName, $isActive)
    {
        $definition = $this->workflowRegistry->getWorkflow($workflowName)->getDefinition();

        if ((bool)$isActive !== $definition->isActive()) {
            $definition->setActive($isActive);
            $this->doctrineHelper->getEntityManager(WorkflowDefinition::class)->flush($definition);
            $this->eventDispatcher->dispatch(
                $isActive ? WorkflowEvents::WORKFLOW_ACTIVATED : WorkflowEvents::WORKFLOW_DEACTIVATED,
                new WorkflowChangesEvent($definition)
            );

            return true;
        }

        return false;
    }

    /**
     * @param string $workflowName
     * @return bool
     */
    public function isActiveWorkflow($workflowName)
    {
        $definition = $this->workflowRegistry->getWorkflow($workflowName)->getDefinition();

        return $definition->isActive();
    }

    /**
     * @param string $workflowName
     */
    public function resetWorkflowData($workflowName)
    {
        $this->getWorkflowItemRepository()->resetWorkflowData($workflowName);
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
     * @param array $recordGroups
     * @return bool
     */
    protected function isStartAllowedByRecordGroups($entity, array $recordGroups)
    {
        $workflowItems = $this->getWorkflowItemsByEntity($entity);
        foreach ($workflowItems as $workflowItem) {
            if (array_intersect($recordGroups, $workflowItem->getDefinition()->getExclusiveRecordGroups())) {
                return false;
            }
        }

        return true;
    }
}
