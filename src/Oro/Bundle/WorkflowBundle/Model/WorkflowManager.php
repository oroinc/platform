<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
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
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ManagerRegistry $registry
     * @param WorkflowRegistry $workflowRegistry
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     */
    public function __construct(
        ManagerRegistry $registry,
        WorkflowRegistry $workflowRegistry,
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager
    ) {
        $this->registry = $registry;
        $this->workflowRegistry = $workflowRegistry;
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
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
        return $this->getApplicableWorkflowByEntityClass(
            $this->doctrineHelper->getEntityClass($entity)
        );
    }

    /**
     * @param string $entityClass
     * @return null|Workflow
     */
    public function getApplicableWorkflowByEntityClass($entityClass)
    {
        return $this->workflowRegistry->getActiveWorkflowByEntityClass($entityClass);
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    public function hasApplicableWorkflowByEntityClass($entityClass)
    {
        return $this->workflowRegistry->hasActiveWorkflowByEntityClass($entityClass);
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
     * @param string|Workflow|WorkflowItem|WorkflowDefinition $workflowIdentifier
     */
    public function activateWorkflow($workflowIdentifier)
    {
        if ($workflowIdentifier instanceof WorkflowDefinition) {
            $entityClass = $workflowIdentifier->getRelatedEntity();
            $workflowName = $workflowIdentifier->getName();
        } else {
            $workflow = $this->getWorkflow($workflowIdentifier);
            $entityClass = $workflow->getDefinition()->getRelatedEntity();
            $workflowName = $workflow->getName();
        }

        $this->setActiveWorkflow($entityClass, $workflowName);
    }

    /**
     * @param string $entityClass
     */
    public function deactivateWorkflow($entityClass)
    {
        $this->setActiveWorkflow($entityClass, null);
    }

    /**
     * @param string $entityClass
     * @param string|null $workflowName
     */
    protected function setActiveWorkflow($entityClass, $workflowName)
    {
        $entityConfig = $this->getEntityConfig($entityClass);
        $entityConfig->set('active_workflow', $workflowName);
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
        if (!$workflowConfigProvider->hasConfig($entityClass)) {
            throw new WorkflowException(sprintf('Entity %s is not configurable', $entityClass));
        }

        return $workflowConfigProvider->getConfig($entityClass);
    }

    /**
     * @param ConfigInterface $entityConfig
     */
    protected function persistEntityConfig(ConfigInterface $entityConfig)
    {
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();
    }
}
