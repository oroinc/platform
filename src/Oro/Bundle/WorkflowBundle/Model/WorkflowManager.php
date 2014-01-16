<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
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
     * @param ManagerRegistry $registry
     * @param WorkflowRegistry $workflowRegistry
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ManagerRegistry $registry,
        WorkflowRegistry $workflowRegistry,
        DoctrineHelper $doctrineHelper
    ) {
        $this->registry = $registry;
        $this->workflowRegistry = $workflowRegistry;
        $this->doctrineHelper = $doctrineHelper;
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
     * @return Collection
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
        array $data = array(),
        Collection $errors = null
    ) {
        $workflow = $this->getWorkflow($workflow);

        return $workflow->isStartTransitionAvailable($transition, $entity, $data, $errors);
    }

    /**
     * @param string $workflow
     * @param object $entity
     * @param string|Transition|null $transition
     * @param array $data
     * @return WorkflowItem
     * @throws \Exception
     */
    public function startWorkflow($workflow, $entity, $transition = null, array $data = array())
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
            $workflowItem->setUpdated();
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
        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        return $this->workflowRegistry->getWorkflowByEntityClass($entityClass);
    }

    /**
     * @param object $entity
     * @return WorkflowItem|null
     */
    public function getWorkflowItemByEntity($entity)
    {
        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $entityIdentifier = $this->doctrineHelper->getEntityIdentifier($entity);

        /** @var WorkflowItemRepository $workflowItemsRepository */
        $workflowItemsRepository = $this->registry->getRepository('OroWorkflowBundle:WorkflowItem');

        return $workflowItemsRepository->findByEntityMetadata($entityClass, $entityIdentifier);
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
        } elseif ($workflowIdentifier instanceof WorkflowItem) {
            return $this->workflowRegistry->getWorkflow($workflowIdentifier->getWorkflowName());
        } elseif ($workflowIdentifier instanceof Workflow) {
            return $workflowIdentifier;
        }

        throw new WorkflowException('Can\'t find workflow by given identifier.');
    }

    /**
     * @param object $entity
     * @param null|string $skippedWorkflow
     * @param null|string $workflowName
     * @param null|string $workflowType
     * @return int
     */
    public function checkWorkflowItemsByEntity(
        $entity,
        $skippedWorkflow = null,
        $workflowName = null,
        $workflowType = null
    ) {
        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $entityIdentifier = $this->doctrineHelper->getEntityIdentifier($entity);
        $workflowItemsRepository = $this->registry->getRepository('OroWorkflowBundle:WorkflowItem');

        return $workflowItemsRepository->checkWorkflowItemsByEntityMetadata(
            $entityClass,
            $entityIdentifier,
            $workflowName,
            $workflowType,
            $skippedWorkflow
        );
    }
}
