<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager as BaseAttributeManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownStepException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;

/**
 * A model that stores all the necessary workflow management functionality.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Workflow
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var AclManager
     */
    protected $aclManager;

    /**
     * @var RestrictionManager
     */
    protected $restrictionManager;

    /**
     * @var StepManager
     */
    protected $stepManager;

    /**
     * @var BaseAttributeManager
     */
    protected $attributeManager;

    /**
     * @var TransitionManager
     */
    protected $transitionManager;

    /**
     * @var VariableManager
     */
    protected $variableManager;

    /**
     * @var Collection
     */
    protected $errors;

    /**
     * @var WorkflowDefinition
     */
    protected $definition;

    /**
     * @var Collection
     */
    protected $restrictions;

    /**
     * @var Collection
     */
    protected $variables;

    protected ?WorkflowItem $stubWorkflowItem = null;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AclManager $aclManager,
        RestrictionManager $restrictionManager,
        StepManager $stepManager = null,
        BaseAttributeManager $attributeManager = null,
        TransitionManager $transitionManager = null,
        VariableManager $variableManager = null
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->aclManager = $aclManager;
        $this->restrictionManager = $restrictionManager;
        $this->stepManager = $stepManager ?: new StepManager();
        $this->attributeManager = $attributeManager ?: new BaseAttributeManager();
        $this->transitionManager = $transitionManager ?: new TransitionManager();
        $this->variableManager = $variableManager ?: new VariableManager();
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->definition->getName();
    }

    /**
     * Get label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->definition->getLabel();
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->definition->isActive();
    }

    /**
     * @return StepManager
     */
    public function getStepManager()
    {
        return $this->stepManager;
    }

    /**
     * @return BaseAttributeManager
     */
    public function getAttributeManager()
    {
        return $this->attributeManager;
    }

    /**
     * @return TransitionManager
     */
    public function getTransitionManager()
    {
        return $this->transitionManager;
    }

    /**
     * @return VariableManager
     */
    public function getVariableManager()
    {
        return $this->variableManager;
    }

    /**
     * Start workflow.
     *
     * @param object $entity
     * @param array $data
     * @param string|Transition $startTransition
     * @param Collection|null $errors
     *
     * @return WorkflowItem
     */
    public function start($entity, array $data = [], $startTransition = null, Collection $errors = null)
    {
        if (null === $startTransition) {
            $startTransition = TransitionManager::DEFAULT_START_TRANSITION_NAME;
        }

        $workflowItem = $this->createWorkflowItem($entity, $data);
        $this->transit($workflowItem, $startTransition, $errors);

        // transition started without related entity, workflow item must be created for specified entity
        if (!$this->doctrineHelper->getSingleEntityIdentifier($entity)) {
            $currentEntity = $workflowItem->getData()->get($this->getDefinition()->getEntityAttributeName());
            $entityClass = $this->doctrineHelper->getEntityClass($currentEntity);
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($currentEntity);

            // find existing workflow item, if transition autostarted inside transit, data will be updated
            if (null === ($currentItem = $this->findWorkflowItem($entityClass, $entityId))) {
                $currentItem = $this->createWorkflowItem($currentEntity, $data);
            }

            return $currentItem->merge($workflowItem);
        }

        return $workflowItem;
    }

    /**
     * Check if transition allowed for workflow item.
     *
     * @param WorkflowItem $workflowItem
     * @param string|Transition $transition
     * @param Collection|null $errors
     * @param bool $fireExceptions
     *
     * @return bool
     * @throws InvalidTransitionException
     */
    public function isTransitionAllowed(
        WorkflowItem $workflowItem,
        $transition,
        Collection $errors = null,
        $fireExceptions = false
    ) {
        // get current transition
        try {
            $transition = $this->transitionManager->extractTransition($transition);
        } catch (InvalidTransitionException $e) {
            if ($fireExceptions) {
                throw $e;
            } else {
                return false;
            }
        }

        $transitionIsValid = $this->checkTransitionValid($transition, $workflowItem, $fireExceptions);

        return $transitionIsValid && $transition->isAllowed($workflowItem, $errors);
    }

    /**
     * Checks whether transition is valid in context of workflow item state.
     *
     * Transition is considered invalid when workflow item is new and transition is not "start".
     * Also transition is considered invalid when current step doesn't contain such allowed transition.
     *
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     * @param bool $fireExceptions
     *
     * @return bool
     * @throws InvalidTransitionException
     */
    public function checkTransitionValid(Transition $transition, WorkflowItem $workflowItem, $fireExceptions)
    {
        // get current step
        $currentStep = null;
        if ($workflowItem->getCurrentStep() && $currentStepName = $workflowItem->getCurrentStep()->getName()) {
            $currentStep = $this->stepManager->getStep($currentStepName);
        }

        // if there is no current step - consider transition as a start transition
        if (!$currentStep) {
            if (!$transition->isStart()) {
                if ($fireExceptions) {
                    throw InvalidTransitionException::notStartTransition(
                        $workflowItem->getWorkflowName(),
                        $transition->getName()
                    );
                }

                return false;
            }
        } elseif (!$currentStep->isAllowedTransition($transition->getName())) {
            // if transition is not allowed for current step
            if ($fireExceptions) {
                throw InvalidTransitionException::stepHasNoAllowedTransition(
                    $workflowItem->getWorkflowName(),
                    $currentStep->getName(),
                    $transition->getName()
                );
            }

            return false;
        }

        return true;
    }

    /**
     * Transit workflow item.
     *
     * @param WorkflowItem $workflowItem
     * @param string|Transition $transition
     * @param Collection|null $errors
     *
     * @throws ForbiddenTransitionException
     * @throws InvalidTransitionException
     * @throws WorkflowException
     */
    public function transit(WorkflowItem $workflowItem, $transition, Collection $errors = null)
    {
        $this->executeAndLogTransit($workflowItem, $transition, true, true, $errors);
    }

    /**
     * Transits a workflow item without checking for preconditions and conditions.
     *
     * @param WorkflowItem $workflowItem
     * @param string|Transition $transition
     *
     * @throws InvalidTransitionException
     * @throws WorkflowException
     */
    public function transitUnconditionally(WorkflowItem $workflowItem, $transition)
    {
        $this->executeAndLogTransit($workflowItem, $transition, false, true);
    }

    public function executeAndLogTransit(
        WorkflowItem $workflowItem,
        $transition,
        bool $checkTransitionAllowance = true,
        bool $checkStepAllowance = true,
        Collection $errors = null
    ): void {
        $transition = $this->transitionManager->extractTransition($transition);

        if ($checkStepAllowance) {
            $this->checkTransitionValid($transition, $workflowItem, true);
        }

        $transitionRecord = $this->createTransitionRecord($workflowItem, $transition);
        if ($checkTransitionAllowance) {
            $transition->transit($workflowItem, $errors);
        } else {
            $transition->transitUnconditionally($workflowItem);
        }
        $workflowItem->addTransitionRecord($transitionRecord);

        $this->aclManager->updateAclIdentities($workflowItem);
        $this->restrictionManager->updateEntityRestrictions($workflowItem);
    }

    /**
     * @param string $entityClass
     * @param int|string $entityId
     * @return null|WorkflowItem
     */
    protected function findWorkflowItem($entityClass, $entityId)
    {
        if (null === $entityId) {
            return $this->stubWorkflowItem;
        }

        /** @var WorkflowItemRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepositoryForClass(WorkflowItem::class);

        return $repo->findOneByEntityMetadata($entityClass, $entityId, $this->getName());
    }

    /**
     * Create workflow item.
     *
     * @param object $entity
     * @param array $data
     *
     * @return WorkflowItem
     */
    public function createWorkflowItem($entity, array $data = [])
    {
        $entityAttributeName = $this->attributeManager->getEntityAttribute()->getName();

        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        $workflowItem = $this->findWorkflowItem($entityClass, $entityId);

        if (!$workflowItem) {
            $workflowItem = new WorkflowItem();
            $workflowItem
                ->setWorkflowName($this->getName())
                ->setEntityClass($entityClass)
                ->setEntityId($entityId)
                ->setEntity($entity);
        }

        if (array_key_exists($entityAttributeName, $data)) {
            unset($data[$entityAttributeName]);
        }

        $workflowData = $workflowItem
            ->getData()
            ->set($entityAttributeName, $entity)
            ->setFieldsMapping($this->getAttributesMapping())
            ->add($data);
        $workflowItem->setDefinition($this->getDefinition());

        // Populates WorkflowData with attributes default values.
        foreach ($this->getAttributeManager()->getAttributes() as $attribute) {
            if ($attribute->getDefault() !== null) {
                $attributeValue = $workflowData->get($attribute->getName());
                if ($attributeValue === null) {
                    $workflowData->set($attribute->getName(), $attribute->getDefault());
                }
            }
        }

        // Populates WorkflowData with variables.
        if ($variables = $this->getVariables()) {
            foreach ($variables as $name => $variable) {
                $workflowData->set($name, $variable->getValue());
            }
        }

        if (!$entityId) {
            $this->stubWorkflowItem = $workflowItem;
        }

        return $workflowItem;
    }

    /**
     * @param string $entityId
     * @return null|WorkflowItem
     */
    public function getWorkflowItemByEntityId($entityId)
    {
        return $this->findWorkflowItem($this->getDefinition()->getRelatedEntity(), $entityId);
    }

    /**
     * Get attribute names mapped to property paths if any.
     *
     * @return array
     */
    public function getAttributesMapping()
    {
        $mapping = [];
        /** @var Attribute $attribute */
        foreach ($this->attributeManager->getAttributes() as $attribute) {
            if ($attribute->getPropertyPath()) {
                $mapping[$attribute->getName()] = $attribute->getPropertyPath();
            }
        }

        return $mapping;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @param Transition $transition
     *
     * @return WorkflowTransitionRecord
     * @throws WorkflowException
     */
    protected function createTransitionRecord(WorkflowItem $workflowItem, Transition $transition)
    {
        $transitionName = $transition->getName();
        $stepFrom = $workflowItem->getCurrentStep();

        $stepName = $transition->getStepTo()->getName();
        $stepTo = $this->getDefinition()->getStepByName($stepName);
        if (!$stepTo) {
            throw new WorkflowException(
                sprintf('Workflow "%s" does not have step entity "%s"', $this->getName(), $stepName)
            );
        }

        $transitionRecord = new WorkflowTransitionRecord();
        $transitionRecord
            ->setTransitionName($transitionName)
            ->setStepFrom($stepFrom)
            ->setStepTo($stepTo);

        return $transitionRecord;
    }

    /**
     * Check that start transition is available to show.
     *
     * @param string|Transition $transition
     * @param object $entity
     * @param array $data
     * @param Collection|null $errors
     *
     * @return bool
     */
    public function isStartTransitionAvailable($transition, $entity, array $data = [], Collection $errors = null)
    {
        $workflowItem = $this->createWorkflowItem($entity, $data);

        return $this->isTransitionAvailable($workflowItem, $transition, $errors);
    }

    /**
     * Check that transition is available to show.
     *
     * @param WorkflowItem $workflowItem
     * @param string|Transition $transition
     * @param Collection|null $errors
     *
     * @return bool
     */
    public function isTransitionAvailable(WorkflowItem $workflowItem, $transition, Collection $errors = null)
    {
        $transition = $this->transitionManager->extractTransition($transition);

        return $transition->isAvailable($workflowItem, $errors);
    }

    /**
     * Get transitions for existing workflow item.
     *
     * @param WorkflowItem $workflowItem
     *
     * @return Collection|Transition[]
     * @throws UnknownStepException
     */
    public function getTransitionsByWorkflowItem(WorkflowItem $workflowItem)
    {
        $currentStepName = $workflowItem->getCurrentStep()->getName();
        $currentStep = $this->stepManager->getStep($currentStepName);
        if (!$currentStep) {
            throw new UnknownStepException($currentStepName);
        }

        $transitions = new ArrayCollection();
        $transitionNames = $currentStep->getAllowedTransitions();
        foreach ($transitionNames as $transitionName) {
            $transition = $this->transitionManager->extractTransition($transitionName);
            $transitions->add($transition);
        }

        return $transitions;
    }

    /**
     * Get passed latest steps from step with minimum order to step with maximum order.
     *
     * @param WorkflowItem $workflowItem
     *
     * @return Collection|Step[]
     */
    public function getPassedStepsByWorkflowItem(WorkflowItem $workflowItem)
    {
        $transitionRecords = $workflowItem->getTransitionRecords();
        $passedSteps = [];
        if ($transitionRecords) {
            $minStepIdx = count($transitionRecords) - 1;
            $minStep = $this->stepManager->getStep($transitionRecords[$minStepIdx]->getStepTo()->getName());
            $steps = [$minStep];
            $minStepIdx--;
            while ($minStepIdx > -1) {
                $step = $this->stepManager->getStep($transitionRecords[$minStepIdx]->getStepTo()->getName());
                if ($step->getOrder() <= $minStep->getOrder() && $step->getName() !== $minStep->getName()) {
                    $minStepIdx--;
                    $minStep = $step;
                    $steps[] = $step;
                } elseif ($step->getName() === $minStep->getName()) {
                    $minStepIdx--;
                } else {
                    break;
                }
            }
            $passedSteps = array_reverse($steps);
        }

        return new ArrayCollection($passedSteps);
    }

    /**
     * @return WorkflowDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param WorkflowDefinition $definition
     *
     * @return Workflow
     */
    public function setDefinition(WorkflowDefinition $definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getRestrictions()
    {
        return $this->restrictions;
    }

    /**
     * @param Collection $restrictions
     *
     * @return Workflow
     */
    public function setRestrictions($restrictions)
    {
        $this->restrictions = $restrictions;

        return $this;
    }

    /**
     * @return array[]
     */
    public function getInitEntities()
    {
        return $this->getConfigurationOption(WorkflowConfiguration::NODE_INIT_ENTITIES, []);
    }

    /**
     * @return array[]
     */
    public function getInitRoutes()
    {
        return $this->getConfigurationOption(WorkflowConfiguration::NODE_INIT_ROUTES, []);
    }

    /**
     * @return array[]
     */
    public function getInitDatagrids()
    {
        return $this->getConfigurationOption(WorkflowConfiguration::NODE_INIT_DATAGRIDS, []);
    }

    /**
     * Returns an array of variables. The class has an internal cache. Calling the method
     * with $refresh parameter true will ignore cache, and assemble variables again
     *
     * @param bool $refresh
     *
     * @return Collection|Variable[]
     */
    public function getVariables($refresh = false)
    {
        if ($refresh || !$this->variables) {
            $manager = $this->getVariableManager();
            $definition = $this->getDefinition();

            $assembler = $manager->getVariableAssembler();
            if (null !== $assembler) {
                $this->variables = $assembler->assemble(
                    $this,
                    $definition->getConfiguration()
                );
            }
        }

        return $this->variables;
    }

    /**
     * @param string $nodeName
     * @param mixed|null $default
     *
     * @return mixed
     */
    private function getConfigurationOption($nodeName, $default = null)
    {
        $configuration = $this->getDefinition()->getConfiguration();

        return $configuration[$nodeName] ?? $default;
    }
}
