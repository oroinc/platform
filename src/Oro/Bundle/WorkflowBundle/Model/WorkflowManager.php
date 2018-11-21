<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowRecordGroupException;
use Oro\Bundle\WorkflowBundle\Model\Tools\StartedWorkflowsBag;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles logic for getting workflow, transitions, workflow items as well as all other related actions
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class WorkflowManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const MASS_START_BATCH_SIZE = 100;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var WorkflowEntityConnector */
    private $entityConnector;

    /** @var WorkflowRegistry */
    private $workflowRegistry;

    /** @var WorkflowApplicabilityFilterInterface[] */
    private $applicabilityFilters = [];

    /** @var StartedWorkflowsBag */
    private $startedWorkflowsBag;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     * @param WorkflowEntityConnector $entityConnector
     * @param StartedWorkflowsBag $startedWorkflowsBag
     */
    public function __construct(
        WorkflowRegistry $workflowRegistry,
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher,
        WorkflowEntityConnector $entityConnector,
        StartedWorkflowsBag $startedWorkflowsBag
    ) {
        $this->workflowRegistry = $workflowRegistry;
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityConnector = $entityConnector;
        $this->startedWorkflowsBag = $startedWorkflowsBag;
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
     * @param WorkflowItem      $workflowItem
     * @param Collection        $errors
     * @return bool
     */
    public function isTransitionAvailable(WorkflowItem $workflowItem, $transition, Collection $errors = null)
    {
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());

        return $workflow->isTransitionAvailable($workflowItem, $transition, $errors);
    }

    /**
     * @param string|Workflow   $workflow
     * @param string|Transition $transition
     * @param object            $entity
     * @param array             $data
     * @param Collection        $errors
     * @return bool
     */
    public function isStartTransitionAvailable(
        $workflow,
        $transition,
        $entity,
        array $data = [],
        Collection $errors = null
    ) {
        //consider to refactor (e.g. remove) type check in favor of string usage only as most cases are.
        //Any way - if developer has Workflow instance already it is possible to get decision from it directly as below
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
     * @param string|Workflow $workflow
     * @param object $entity
     * @param string|Transition|null $transition
     * @param array $data
     * @param bool $throwGroupException
     *
     * @return WorkflowItem|null
     * @throws WorkflowRecordGroupException
     */
    public function startWorkflow($workflow, $entity, $transition = null, array $data = [], $throwGroupException = true)
    {
        try {
            $workflowItem = $this->doStartWorkflow($workflow, $entity, $transition, $data);
        } catch (WorkflowRecordGroupException $exception) {
            if ($throwGroupException) {
                throw $exception;
            }

            return null;
        }

        if ($workflowItem) {
            $workflowItem = $this->inTransaction(
                function (EntityManager $em) use ($workflowItem) {
                    $em->persist($workflowItem);
                    $em->flush();

                    return $workflowItem;
                },
                WorkflowItem::class
            );
        }

        return $workflowItem;
    }

    /**
     * @param string|Workflow $workflow
     * @param object $entity
     * @param string|Transition|null $transition
     * @param array $data
     * @param array $workflowItems
     *
     * @return WorkflowItem|null
     *
     * @throws WorkflowRecordGroupException
     */
    protected function doStartWorkflow(
        $workflow,
        $entity,
        $transition = null,
        array $data = [],
        array $workflowItems = []
    ) {
        //consider to refactor (e.g. remove) type check in favor of string usage only as most cases are
        $workflow = $this->getWorkflow($workflow);

        //If transition passed as "null", then check if default "start" transition allowed
        if (!$transition) {
            $transition = $workflow->getTransitionManager()->getDefaultStartTransition();

            if (!$workflow->isStartTransitionAvailable($transition, $entity)) {
                return null;
            }
        }

        $transition = $workflow->getTransitionManager()->getStartTransition($transition);
        if (!$this->isStartAllowedForEntity($workflow, $entity)) {
            return null;
        }

        //Collect and merge existing workflow items
        $workflowItems = array_merge($workflowItems, $this->getWorkflowItemsByEntity($entity));

        if (!$this->isStartAllowedByRecordGroups($entity, $workflow, $workflowItems)) {
            $message = sprintf(
                'Workflow "%s" can not be started because it belongs to exclusive_record_group ' .
                'with already started other workflow for this entity',
                $workflow->getName()
            );
            if ($this->logger) {
                $this->logger->error($message, [
                    'workflow' => $workflow->getName(),
                    'transition' => ($transition instanceof Transition) ? $transition->getName() : $transition,
                    'entityClass' => ClassUtils::getClass($entity),
                    'entityId' => $this->doctrineHelper->getSingleEntityIdentifier($entity),
                    'data' => $data,
                ]);
            }
            throw new WorkflowRecordGroupException($message);
        }

        $this->unsetStartedWorkflowForEntity($workflow, $entity);

        return $workflow->start($entity, $data, $transition);
    }

    /**
     * Start several workflows
     *
     * @param array|WorkflowStartArguments[] $startArgumentsList instances of WorkflowStartArguments
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function massStartWorkflow(array $startArgumentsList)
    {
        $startArgumentsList = array_filter($startArgumentsList, function ($item) {
            return $item instanceof WorkflowStartArguments;
        });

        if (!$startArgumentsList) {
            return;
        }

        $startArgumentsChunks = array_chunk($startArgumentsList, self::MASS_START_BATCH_SIZE);
        $em = $this->doctrineHelper->getEntityManagerForClass(WorkflowItem::class);

        foreach ($startArgumentsChunks as $key => $chunk) {
            $workflowItems = [];
            $em->beginTransaction();

            try {
                /** @var WorkflowStartArguments $startArguments */
                foreach ($chunk as $startArguments) {
                    $entity = $startArguments->getEntity();

                    $hash = spl_object_hash($entity);
                    if (!array_key_exists($hash, $workflowItems)) {
                        $workflowItems[$hash] = [];
                    }

                    try {
                        $workflowItem = $this->doStartWorkflow(
                            $startArguments->getWorkflowName(),
                            $entity,
                            $startArguments->getTransition(),
                            $startArguments->getData(),
                            $workflowItems[$hash]
                        );
                    } catch (WorkflowRecordGroupException $exception) {
                        continue;
                    }

                    if ($workflowItem) {
                        $em->persist($workflowItem);
                        $workflowItems[$hash][] = $workflowItem;
                    }
                }

                if ($workflowItems) {
                    $em->flush();
                }

                $em->commit();
            } catch (\Exception $exception) {
                $em->rollback();
                if ($this->logger) {
                    $this->logger->critical('Workflow mass start transition exception', ['exception' => $exception]);
                }
            } finally {
                unset($startArgumentsChunks[$key]);
            }
        }
    }

    /**
     * Perform workflow item transition.
     *
     * @param WorkflowItem      $workflowItem
     * @param string|Transition $transition
     */
    public function transit(WorkflowItem $workflowItem, $transition)
    {
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());

        $this->transitWorkflow($workflow, $workflowItem, $transition);
    }

    /**
     * @param Workflow     $workflow
     * @param WorkflowItem $workflowItem
     * @param string       $transition
     */
    private function transitWorkflow(Workflow $workflow, WorkflowItem $workflowItem, $transition)
    {
        $this->inTransaction(
            function (EntityManager $em) use ($workflow, $workflowItem, $transition) {
                $workflow->transit($workflowItem, $transition);
                $workflowItem->setUpdated(); // transition might not change workflow item
                $em->flush();

                if ($this->logger) {
                    $this->logger->info(
                        'Workflow transition is complete',
                        [
                            'workflow'     => $workflow,
                            'workflowItem' => $workflowItem,
                            'transition'   => $transition
                        ]
                    );
                }
            },
            WorkflowItem::class
        );
    }

    /**
     * Tries to transit workflow and checks weather given transition is allowed.
     * Returns true on success - false otherwise.
     * @param WorkflowItem $workflowItem
     * @param string       $transition
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
     * @param string   $entityClass
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
     * @return Workflow[]|array
     * @throws \InvalidArgumentException
     */
    public function getApplicableWorkflows($entity)
    {
        if (!$this->entityConnector->isApplicableEntity($entity)) {
            return [];
        }

        $workflows = $this->workflowRegistry->getActiveWorkflowsByEntityClass(
            $this->doctrineHelper->getEntityClass($entity)
        );

        if (is_object($entity)) {
            $recordContext = new WorkflowRecordContext($entity);

            foreach ($this->applicabilityFilters as $applicabilityFilter) {
                $workflows = $applicabilityFilter->filter($workflows, $recordContext);
            }
        }

        return $workflows->toArray();
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
     * @param $entity
     * @return false|WorkflowItem
     */
    public function getFirstWorkflowItemByEntity($entity)
    {
        $items = $this->getWorkflowItemsByEntity($entity);

        return \reset($items);
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
        $result = $this->setWorkflowStatus($workflowName, false);

        if ($result) {
            $this->startedWorkflowsBag->removeWorkflow($workflowName);
        }

        return $result;
    }

    /**
     * @param string $workflowName
     * @param bool   $isActive
     * @return bool weather workflow was changed his state
     */
    private function setWorkflowStatus($workflowName, $isActive)
    {
        $definition = $this->workflowRegistry->getWorkflow($workflowName)->getDefinition();

        if ((bool) $isActive !== $definition->isActive()) {
            $this->eventDispatcher->dispatch(
                $isActive ? WorkflowEvents::WORKFLOW_BEFORE_ACTIVATION : WorkflowEvents::WORKFLOW_BEFORE_DEACTIVATION,
                new WorkflowChangesEvent($definition)
            );

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
     * Checks weather workflow with such name is active in refreshed instance.
     * @param string $workflowName
     * @return bool
     */
    public function isActiveWorkflow($workflowName)
    {
        return $this->workflowRegistry->getWorkflow($workflowName)->isActive();
    }

    /**
     * @param string $workflowName
     */
    public function resetWorkflowData($workflowName)
    {
        $this->getWorkflowItemRepository()->resetWorkflowData($workflowName);
        $this->startedWorkflowsBag->removeWorkflow($workflowName);
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
     * @param Workflow $workflow
     * @param array $workflowItems
     *
     * @return bool
     */
    protected function isStartAllowedByRecordGroups($entity, Workflow $workflow, array $workflowItems = [])
    {
        //Allow to start for new entities
        if (null === $this->doctrineHelper->getSingleEntityIdentifier($entity)) {
            return true;
        }

        foreach ($workflowItems as $workflowItem) {
            $result = array_intersect(
                $workflow->getDefinition()->getExclusiveRecordGroups(),
                $workflowItem->getDefinition()->getExclusiveRecordGroups()
            );
            if ($result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return false if the Workflow already started for the Entity
     *
     * @param Workflow $workflow
     * @param object $entity
     *
     * @return bool
     */
    protected function isStartAllowedForEntity(Workflow $workflow, $entity)
    {
        $startedWorkflowsBag = $this->startedWorkflowsBag;
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        $workflowName = $workflow->getName();

        if ($entityId && $startedWorkflowsBag->hasWorkflowEntity($workflowName)) {
            foreach ($startedWorkflowsBag->getWorkflowEntities($workflowName) as $startedEntity) {
                $startedEntityId = $this->doctrineHelper->getSingleEntityIdentifier($startedEntity);
                if ($startedEntityId && ($startedEntityId === $entityId)) {
                    return false;
                }
            }
        }

        $startedWorkflowsBag->addWorkflowEntity($workflowName, $entity);

        return true;
    }

    /**
     * Unset started workflow for entity
     *
     * @param Workflow $workflow
     * @param object $entity
     */
    protected function unsetStartedWorkflowForEntity(Workflow $workflow, $entity)
    {
        $startedWorkflowsBag = $this->startedWorkflowsBag;
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        $workflowName = $workflow->getName();

        if ($entityId === null || !$startedWorkflowsBag->hasWorkflowEntity($workflowName)) {
            return;
        }

        foreach ($startedWorkflowsBag->getWorkflowEntities($workflowName) as $startedEntity) {
            if ($this->doctrineHelper->getSingleEntityIdentifier($startedEntity) === $entityId) {
                $startedWorkflowsBag->removeWorkflowEntity($workflowName, $startedEntity);
                break;
            }
        }
    }

    /**
     * @param WorkflowApplicabilityFilterInterface $applicabilityFilter
     */
    public function addApplicabilityFilter(WorkflowApplicabilityFilterInterface $applicabilityFilter)
    {
        $this->applicabilityFilters[] = $applicabilityFilter;
    }
}
