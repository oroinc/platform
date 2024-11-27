<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\EventDispatcher;
use Oro\Bundle\WorkflowBundle\Event\Transition\AnnounceEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\GuardEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\PreAnnounceEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\PreGuardEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\StepEnteredEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\StepEnterEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\StepLeaveEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionCompletedEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\WorkflowFinishEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\WorkflowStartEvent;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * A model that stores all the necessary functionality for transferring between workflow states.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Transition
{
    /** @var string */
    protected $name;

    /** @var Step */
    protected $stepTo;

    /** @var string|array|null */
    protected $aclResource;

    /** @var string|null */
    protected $aclMessage;

    /** @var array */
    protected $conditionalStepsTo = [];

    /** @var string */
    protected $label;

    /** @var string */
    protected $buttonLabel;

    /** @var string */
    protected $buttonTitle;

    /** @var ExpressionInterface|null */
    protected $condition;

    /** @var ExpressionInterface|null */
    protected $preCondition;

    /** @var ActionInterface|null */
    protected $preAction;

    /** @var ActionInterface|null */
    protected $action;

    /** @var bool */
    protected $start = false;

    /** @var bool */
    protected $hidden = false;

    /** @var array */
    protected $frontendOptions = array();

    /** @var string */
    protected $formType;

    /** @var string */
    protected $displayType;

    /** @var array */
    protected $formOptions = array();

    /** @var string */
    protected $message;

    /** @var bool */
    protected $unavailableHidden = false;

    /** @var string */
    protected $destinationPage;

    /** @var string */
    protected $pageTemplate;

    /** @var string */
    protected $dialogTemplate;

    /** @var string */
    protected $scheduleCron;

    /** @var string */
    protected $scheduleFilter;

    /** @var bool */
    protected $scheduleCheckConditions = false;

    /** @var array */
    protected $initEntities = [];

    /** @var array */
    protected $initRoutes = [];

    /** @var array */
    protected $initDatagrids = [];

    /** @var string */
    protected $initContextAttribute;

    /** @var bool */
    protected $hasFormConfiguration = false;

    /** @var TransitionOptionsResolver */
    protected $optionsResolver;

    /** @var EventDispatcher */
    protected $eventDispatcher;

    /** @var TransitionServiceInterface|null */
    protected $transitionService;

    public function __construct(TransitionOptionsResolver $optionsResolver, EventDispatcher $eventDispatcher)
    {
        $this->optionsResolver = $optionsResolver;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Set label.
     *
     * @param string $label
     * @return Transition
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $buttonLabel
     *
     * @return Transition
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->buttonLabel = $buttonLabel;

        return $this;
    }

    /**
     * @return string
     */
    public function getButtonLabel()
    {
        return $this->buttonLabel;
    }

    /**
     * @param string $buttonTitle
     *
     * @return Transition
     */
    public function setButtonTitle($buttonTitle)
    {
        $this->buttonTitle = $buttonTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getButtonTitle()
    {
        return $this->buttonTitle;
    }

    /**
     * Set condition.
     *
     * @param ExpressionInterface|null $condition
     * @return Transition
     */
    public function setCondition(ExpressionInterface $condition = null)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get condition.
     *
     * @return ExpressionInterface|null
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Set pre-condition.
     *
     * @param ExpressionInterface|null $condition
     * @return Transition
     */
    public function setPreCondition($condition)
    {
        $this->preCondition = $condition;

        return $this;
    }

    /**
     * Get pre-condition.
     *
     * @return ExpressionInterface|null
     */
    public function getPreCondition()
    {
        return $this->preCondition;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Transition
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param ActionInterface|null $preAction
     * @return Transition
     */
    public function setPreAction(ActionInterface $preAction = null)
    {
        $this->preAction = $preAction;

        return $this;
    }

    /**
     * @return ActionInterface|null
     */
    public function getPreAction()
    {
        return $this->preAction;
    }

    /**
     * @param ActionInterface|null $action
     * @return Transition
     */
    public function setAction(ActionInterface $action = null)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return ActionInterface|null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set step to.
     *
     * @param Step $stepTo
     * @return Transition
     */
    public function setStepTo(Step $stepTo)
    {
        $this->stepTo = $stepTo;

        return $this;
    }

    /**
     * Get step to.
     *
     * @return Step
     */
    public function getStepTo()
    {
        return $this->stepTo;
    }

    /**
     * Get resolved step to.
     *
     * If any of conditional steps matches its condition then the conditional step to will be returned,
     * default step_to is added to the list of conditional steps by TransitionAssembler, and it is checked in the loop
     *
     * If there are no conditional steps then the default step_to will be returned.
     */
    public function getResolvedStepTo(WorkflowItem $workflowItem): Step
    {
        foreach ($this->conditionalStepsTo as $conditionalStepConfig) {
            if ($conditionalStepConfig['condition']->evaluate($workflowItem)) {
                return $conditionalStepConfig['step'];
            }
        }

        if (!$this->conditionalStepsTo) {
            return $this->getStepTo();
        }

        throw new ForbiddenTransitionException(
            sprintf('Transition "%s" is not allowed.', $this->getName())
        );
    }

    public function addConditionalStepTo(Step $stepTo, ExpressionInterface $condition)
    {
        $this->conditionalStepsTo[$stepTo->getName()] = [
            'step' => $stepTo,
            'condition' => $condition
        ];

        return $this;
    }

    public function getConditionalStepsTo(): array
    {
        return $this->conditionalStepsTo;
    }

    public function getAclResource(): string|array|null
    {
        return $this->aclResource;
    }

    public function setAclResource(string|array|null $aclResource): self
    {
        $this->aclResource = $aclResource;

        return $this;
    }

    public function getAclMessage(): ?string
    {
        return $this->aclMessage;
    }

    public function setAclMessage(?string $aclMessage): self
    {
        $this->aclMessage = $aclMessage;

        return $this;
    }

    /**
     * Check is transition condition is allowed for current workflow item.
     *
     * @param WorkflowItem $workflowItem
     * @param Collection|null $errors
     * @return boolean
     */
    protected function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null)
    {
        // Pre-guard transition to be able to block transition on early stages
        // without a need to execute conditions.
        $event = new PreGuardEvent($workflowItem, $this, true, $errors);
        $this->eventDispatcher->dispatch($event, $this->getName());

        if (!$event->isAllowed()) {
            return false;
        }

        $workflowItem->lock();
        // Execute check that transition service allows the transition or check conditions.
        $isAllowed = true;
        if ($this->transitionService) {
            $isAllowed = $this->transitionService->isConditionAllowed($workflowItem, $errors);
        } elseif ($this->condition) {
            $isAllowed = (bool)$this->condition->evaluate($workflowItem, $errors);
        }
        $workflowItem->unlock();

        $event = new GuardEvent($workflowItem, $this, $isAllowed, $errors);
        $this->eventDispatcher->dispatch($event, $this->getName());

        return $event->isAllowed();
    }

    /**
     * Check is transition pre-condition is allowed for current workflow item.
     *
     * @param WorkflowItem $workflowItem
     * @param Collection|null $errors
     * @return boolean
     */
    protected function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null)
    {
        // Pre-announce transition to be able to block transition availability on early stages
        // without a need to execute pre-actions and pre-conditions.
        $preAnnounceEvent = new PreAnnounceEvent($workflowItem, $this, true, $errors);
        $this->eventDispatcher->dispatch($preAnnounceEvent, $this->getName());

        if (!$preAnnounceEvent->isAllowed()) {
            return false;
        }

        $workflowItem->lock();
        // Execute pre-actions and pre-conditions
        $isAllowed = true;
        if ($this->transitionService) {
            $isAllowed = $this->transitionService->isPreConditionAllowed($workflowItem, $errors);
        } elseif ($this->preCondition || $this->preAction) {
            $this->preAction?->execute($workflowItem);

            $isAllowed = !$this->preCondition || $this->preCondition->evaluate($workflowItem, $errors);
        }
        $workflowItem->unlock();

        $announceEvent = new AnnounceEvent($workflowItem, $this, $isAllowed, $errors);
        $this->eventDispatcher->dispatch($announceEvent, $this->getName());

        return $announceEvent->isAllowed();
    }

    /**
     * Check is transition allowed for current workflow item.
     *
     * @param WorkflowItem $workflowItem
     * @param Collection|null $errors
     * @return bool
     */
    public function isAllowed(WorkflowItem $workflowItem, Collection $errors = null)
    {
        return $this->isPreConditionAllowed($workflowItem, $errors)
            && $this->isConditionAllowed($workflowItem, $errors);
    }

    /**
     * Check that transition is available to show.
     *
     * @param WorkflowItem $workflowItem
     * @param Collection|null $errors
     * @return bool
     */
    public function isAvailable(WorkflowItem $workflowItem, Collection $errors = null)
    {
        $result = $this->hasForm()
            ? $this->isPreConditionAllowed($workflowItem, $errors)
            : $this->isAllowed($workflowItem, $errors);

        $this->optionsResolver->resolveTransitionOptions($this, $workflowItem);

        return $result;
    }

    /**
     * Run transition process.
     *
     * @throws ForbiddenTransitionException
     */
    public function transit(WorkflowItem $workflowItem, Collection $errors = null)
    {
        if ($workflowItem->isLocked()) {
            throw new WorkflowException('Can not transit locked WorkflowItem. Transit is allowed only in "actions".');
        }

        if ($this->isAllowed($workflowItem, $errors)) {
            $this->transitUnconditionally($workflowItem);
        } else {
            throw new ForbiddenTransitionException(
                sprintf('Transition "%s" is not allowed.', $this->getName())
            );
        }
    }

    /**
     * Makes transition without checking for preconditions and conditions.
     */
    public function transitUnconditionally(WorkflowItem $workflowItem): void
    {
        $transitionEvent = new TransitionEvent($workflowItem, $this);

        $stepTo = $this->changeCurrentStep($workflowItem);

        $this->eventDispatcher->dispatch($transitionEvent, $this->getName());
        if ($this->transitionService) {
            $this->transitionService->execute($workflowItem);
        } elseif ($this->action) {
            $this->action->execute($workflowItem);
        }

        $completedEvent = new TransitionCompletedEvent($workflowItem, $this);
        $this->eventDispatcher->dispatch($completedEvent, $this->getName());

        if ($stepTo?->isFinal()) {
            $finishEvent = new WorkflowFinishEvent($workflowItem, $this);
            $this->eventDispatcher->dispatch($finishEvent);
        }
    }

    private function changeCurrentStep(WorkflowItem $workflowItem): ?Step
    {
        // Do not change current step if workflow entity does not exist.
        if (!$workflowItem->getEntityId()) {
            return null;
        }

        $currentStep = $workflowItem->getCurrentStep();
        if ($currentStep) {
            $leaveEvent = new StepLeaveEvent($workflowItem, $this);
            $this->eventDispatcher->dispatch($leaveEvent, $currentStep->getName());
        } else {
            $startEvent = new WorkflowStartEvent($workflowItem, $this);
            $this->eventDispatcher->dispatch($startEvent);
        }

        $stepTo = $this->getResolvedStepTo($workflowItem);
        // Do not enter the same step again
        if ($workflowItem->getCurrentStep()?->getName() !== $stepTo->getName()) {
            $enterEvent = new StepEnterEvent($workflowItem, $this);
            $this->eventDispatcher->dispatch($enterEvent, $stepTo->getName());

            $workflowItem->setCurrentStep($workflowItem->getDefinition()->getStepByName($stepTo->getName()));

            $enteredEvent = new StepEnteredEvent($workflowItem, $this);
            $this->eventDispatcher->dispatch($enteredEvent, $stepTo->getName());
        }

        return $stepTo;
    }

    /**
     * Mark transition as start transition
     *
     * @param boolean $start
     * @return Transition
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @return bool
     */
    public function isStart()
    {
        return $this->start;
    }

    /**
     * Set frontend options.
     *
     * @param array $frontendOptions
     * @return Transition
     */
    public function setFrontendOptions(array $frontendOptions)
    {
        $this->frontendOptions = $frontendOptions;

        return $this;
    }

    /**
     * Get frontend options.
     *
     * @return array
     */
    public function getFrontendOptions()
    {
        return $this->frontendOptions;
    }

    /**
     * @return bool
     */
    public function hasForm()
    {
        return (!empty($this->formOptions) && !empty($this->formOptions['attribute_fields']))
            || $this->hasFormConfiguration() || $this->getDisplayType() === 'page';
    }

    /**
     * @param string $formType
     * @return Transition
     */
    public function setFormType($formType)
    {
        $this->formType = $formType;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @param array $formOptions
     * @return Transition
     */
    public function setFormOptions(array $formOptions)
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getFormOptions()
    {
        return $this->formOptions;
    }

    /**
     * @return boolean
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * @param boolean $hidden
     * @return Transition
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return Transition
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isUnavailableHidden()
    {
        return $this->unavailableHidden;
    }

    /**
     * @param boolean $unavailableHidden
     * @return Transition
     */
    public function setUnavailableHidden($unavailableHidden)
    {
        $this->unavailableHidden = $unavailableHidden;

        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayType()
    {
        return $this->displayType;
    }

    /**
     * @param string $displayType
     * @return Transition
     */
    public function setDisplayType($displayType)
    {
        $this->displayType = $displayType;

        return $this;
    }

    /**
     * @param string $destinationPage
     * @return Transition
     */
    public function setDestinationPage($destinationPage)
    {
        $this->destinationPage = $destinationPage;

        return $this;
    }

    /**
     * @return string
     */
    public function getDestinationPage()
    {
        return $this->destinationPage;
    }

    /**
     * @param string $transitionTemplate
     * @return Transition
     */
    public function setPageTemplate($transitionTemplate)
    {
        $this->pageTemplate = $transitionTemplate;

        return $this;
    }

    /**
     * @return string
     */
    public function getPageTemplate()
    {
        return $this->pageTemplate;
    }

    /**
     * @param string $widgetTemplate
     * @return Transition
     */
    public function setDialogTemplate($widgetTemplate)
    {
        $this->dialogTemplate = $widgetTemplate;

        return $this;
    }

    /**
     * @return string
     */
    public function getDialogTemplate()
    {
        return $this->dialogTemplate;
    }

    /**
     * @param string $cron
     * @return $this
     */
    public function setScheduleCron($cron)
    {
        $this->scheduleCron = (string)$cron;

        return $this;
    }

    /**
     * @return string
     */
    public function getScheduleCron()
    {
        return $this->scheduleCron;
    }

    /**
     * @param string $dqlFilter
     * @return $this
     */
    public function setScheduleFilter($dqlFilter)
    {
        $this->scheduleFilter = (string)$dqlFilter;

        return $this;
    }

    /**
     * @return string
     */
    public function getScheduleFilter()
    {
        return $this->scheduleFilter;
    }

    /**
     * @param bool $scheduleCheckConditions
     * @return $this
     */
    public function setScheduleCheckConditions($scheduleCheckConditions)
    {
        $this->scheduleCheckConditions = $scheduleCheckConditions;

        return $this;
    }

    /**
     * @return bool
     */
    public function isScheduleCheckConditions()
    {
        return $this->scheduleCheckConditions;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * @return array
     */
    public function getInitEntities()
    {
        return $this->initEntities;
    }

    /**
     * @param array $initEntities
     *
     * @return $this
     */
    public function setInitEntities(array $initEntities)
    {
        $this->initEntities = $initEntities;

        return $this;
    }

    /**
     * @return array
     */
    public function getInitRoutes()
    {
        return $this->initRoutes;
    }

    /**
     * @param array $initRoutes
     *
     * @return $this
     */
    public function setInitRoutes(array $initRoutes)
    {
        $this->initRoutes = $initRoutes;

        return $this;
    }

    /**
     * @return array
     */
    public function getInitDatagrids()
    {
        return $this->initDatagrids;
    }

    /**
     * @param array $initDatagrids
     *
     * @return $this
     */
    public function setInitDatagrids(array $initDatagrids)
    {
        $this->initDatagrids = $initDatagrids;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmptyInitOptions()
    {
        return !count($this->getInitEntities()) && !count($this->getInitRoutes()) && !count($this->getInitDatagrids());
    }

    /**
     * @return string
     */
    public function getInitContextAttribute()
    {
        return $this->initContextAttribute;
    }

    /**
     * @param string $initContextAttribute
     *
     * @return $this
     */
    public function setInitContextAttribute($initContextAttribute)
    {
        $this->initContextAttribute = $initContextAttribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormHandler()
    {
        return $this->formOptions[WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION]['handler'];
    }

    /**
     * @return string
     */
    public function getFormDataAttribute()
    {
        return $this->formOptions[WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION]['data_attribute'];
    }

    /**
     * @return string
     */
    public function getFormTemplate()
    {
        return $this->formOptions[WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION]['template'];
    }

    /**
     * @return string
     */
    public function getFormDataProvider()
    {
        return $this->formOptions[WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION]['data_provider'];
    }

    /**
     * @return boolean
     */
    public function hasFormConfiguration()
    {
        return !empty($this->formOptions[WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION]);
    }

    public function setTransitionService(?TransitionServiceInterface $transitionService): self
    {
        $this->transitionService = $transitionService;

        return $this;
    }
}
