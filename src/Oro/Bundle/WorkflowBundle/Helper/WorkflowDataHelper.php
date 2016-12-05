<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Tools\WorkflowStepHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Helper for getting entity workflows data
 */
class WorkflowDataHelper
{
    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var UrlGeneratorInterface */
    protected $router;

    /**
     * @param WorkflowManager $workflowManager
     * @param SecurityFacade $securityFacade
     * @param TranslatorInterface $translator
     * @param UrlGeneratorInterface $router
     */
    public function __construct(
        WorkflowManager $workflowManager,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator,
        UrlGeneratorInterface $router
    ) {
        $this->workflowManager = $workflowManager;
        $this->securityFacade = $securityFacade;
        $this->translator = $translator;
        $this->router = $router;
    }

    /**
     * Returns array with workflows data applicable for $entity
     *
     * @param object $entity
     *
     * @return array
     */
    public function getEntityWorkflowsData($entity)
    {
        $applicableWorkflows = array_filter(
            $this->workflowManager->getApplicableWorkflows($entity),
            function (Workflow $workflow) use ($entity) {
                return $this->isWorkflowPermissionGranted('VIEW_WORKFLOW', $workflow->getName(), $entity);
            }
        );

        return array_map(
            function (Workflow $workflow) use ($entity) {
                return $this->getWorkflowData($entity, $workflow);
            },
            $applicableWorkflows
        );
    }

    /**
     * Return array with serialized workflow data for $entity
     *
     * @param object $entity
     * @param Workflow $workflow
     *
     * @return array
     */
    protected function getWorkflowData($entity, Workflow $workflow)
    {
        $transitionsData = [];
        $workflowItem = $this->workflowManager->getWorkflowItem($entity, $workflow->getName());

        $transitions = $workflowItem
            ? $this->getAllowedTransitions($workflow, $workflowItem)
            : $this->getAllowedStartTransitions($workflow, $entity);

        $workflowItemId = $workflowItem !== null ? $workflowItem->getId() : null;

        foreach ($transitions as $transition) {
            $transitionsData[] = array_merge(
                $this->getTransitionData($transition),
                $this->getTransitionUrl($transition, $entity, $workflow, $workflowItem)
            );
        }

        return [
            'name' => $workflow->getName(),
            'label' => $workflow->getLabel(),
            'isStarted' => $workflowItemId !== null,
            'workflowItemId' => $workflowItemId,
            'stepsData' => $this->getStepsData($entity, $workflow, $workflowItem, $transitions),
            'transitionsData' => $transitionsData,
        ];
    }

    /**
     * @param Transition $transition
     *
     * @return array
     */
    protected function getTransitionData(Transition $transition)
    {
        $messageTranslation = $this->translator->trans($transition->getMessage(), [], 'workflows');

        // Do not show untranslated messages (i.e. user left the message input empty)
        if (!$transition->getMessage() || $messageTranslation === $transition->getMessage()) {
            $messageTranslation = '';
        }

        return [
            'name' => $transition->getName(),
            'label' => $this->translator->trans($transition->getLabel(), [], 'workflows'),
            'isStart' => $transition->isStart(),
            'hasForm' => $transition->hasForm(),
            'displayType' => $transition->getDisplayType(),
            'message' => $messageTranslation,
            'frontendOptions' => $transition->getFrontendOptions(),
        ];
    }

    /**
     * @param Transition $transition
     * @param $entity
     * @param Workflow $workflow
     * @param WorkflowItem $workflowItem
     *
     * @return string|null
     */
    protected function getTransitionUrl(
        Transition $transition,
        $entity,
        Workflow $workflow,
        WorkflowItem $workflowItem = null
    ) {
        $isStarted = $workflowItem !== null;

        if ($isStarted) {
            $urlParams = [
                'transitionName' => $transition->getName(),
                'workflowItemId' => $workflowItem->getId(),
            ];

            if ($transition->getDisplayType() === 'dialog') {
                if ($transition->hasForm()) {
                    return [
                        'dialogUrl' => $this->router->generate(
                            'oro_workflow_widget_transition_form',
                            $urlParams,
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                        'transitionUrl' => $this->router->generate(
                            'oro_api_workflow_transit',
                            $urlParams,
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                    ];
                }
            }

            return [
                'transitionUrl' => $this->router->generate(
                    'oro_api_workflow_transit',
                    $urlParams,
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ];
        }

        $urlParams = [
            'workflowName' => $workflow->getName(),
            'transitionName' => $transition->getName(),
            'entityId' => $entity->getId(),
        ];

        if ($transition->getDisplayType() === 'dialog') {
            if ($transition->hasForm()) {
                return [
                    'dialogUrl' => $this->router->generate(
                        'oro_workflow_widget_start_transition_form',
                        $urlParams,
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    'transitionUrl' => $this->router->generate(
                        'oro_api_workflow_start',
                        $urlParams,
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                ];
            }

            return [
                'transitionUrl' => $this->router->generate(
                    'oro_api_workflow_start',
                    $urlParams,
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ];
        }

        return [
            'transitionUrl' => $this->router->generate(
                'oro_workflow_start_transition_form',
                $urlParams,
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
    }

    /**
     * Return serialized steps data
     *
     * @param object $entity
     * @param Workflow $workflow
     * @param WorkflowItem|null $workflowItem
     * @param array $transitions
     *
     * @return array
     */
    protected function getStepsData(
        $entity,
        Workflow $workflow,
        WorkflowItem $workflowItem = null,
        array $transitions = []
    ) {
        $isStepsDisplayOrdered = $workflow->getDefinition()->isStepsDisplayOrdered();
        $currentStep = $workflowItem ? $workflowItem->getCurrentStep() : null;

        $helper = new WorkflowStepHelper($workflow);
        $stepManager = $workflow->getStepManager();

        if ($isStepsDisplayOrdered) {
            $steps = $stepManager->getOrderedSteps(true, true)->toArray();

            if ($workflowItem) {
                $startStepNames = $this->getStartStepNames($entity, $workflow);

                $way = array_merge(
                    $helper->getStepsBefore($workflowItem, $startStepNames, true),
                    $helper->getStepsAfter($stepManager->getStep($currentStep->getName()), true, true)
                );

                $steps = array_intersect($steps, $way);
            }

            $steps = array_map(
                function ($stepName) use ($stepManager) {
                    return $stepManager->getStep($stepName);
                },
                $steps
            );
        } elseif ($currentStep) {
            $steps = [$stepManager->getStep($currentStep->getName())];
        } else {
            $steps = array_map(
                function (Transition $transition) {
                    return $transition->getStepTo();
                },
                $transitions
            );
        }

        $steps = array_map(
            function (Step $step) use ($currentStep, $helper) {
                return [
                    'label' => $step->getLabel(),
                    'active' => $currentStep && $step->getName() === $currentStep->getName(),
                    'possibleStepsCount' => count($helper->getStepsAfter($step)),
                ];
            },
            $steps
        );

        return [
            'isOrdered' => $isStepsDisplayOrdered,
            'steps' => $steps,
        ];
    }

    /**
     * Get step names from start transitions
     *
     * @param $entity
     * @param Workflow $workflow
     *
     * @return array
     */
    protected function getStartStepNames($entity, Workflow $workflow)
    {
        return array_map(
            function (array $data) {
                return $data['transition']->getStepTo()->getName();
            },
            $this->getAllowedStartTransitions($workflow, $entity)
        );
    }

    /**
     * Get transitions data for view based on workflow item.

*
     * @param Workflow $workflow
     * @param WorkflowItem $workflowItem
     *
     * @return Transition[]
     */
    protected function getAllowedTransitions(Workflow $workflow, WorkflowItem $workflowItem)
    {
        $transitions = [];
        $availableTransitions = $workflow->getTransitionsByWorkflowItem($workflowItem);

        foreach ($availableTransitions as $transition) {
            if ($this->isTransitionAllowed($workflow, $transition, $workflowItem)) {
                $transitions[$transition->getName()] = $transition;
            }
        }

        return $transitions;
    }

    /**
     * Get array of start transitions available for workflow
     *
     *@param Workflow $workflow
     * @param object $entity
     *
     * @return Transition[]
     */
    protected function getAllowedStartTransitions(Workflow $workflow, $entity)
    {
        $transitions = [];
        $startTransitions = $workflow->getTransitionManager()->getStartTransitions();

        foreach ($startTransitions as $startTransition) {
            if ($this->isStartTransitionAllowed($workflow, $startTransition, $entity)) {
                $transitions[$startTransition->getName()] = $startTransition;
            }
        }

        if (!empty($transitions) || !$workflow->getStepManager()->hasStartStep()) {
            return $transitions;
        }

        // extra case to show start transition
        $defaultStartTransition = $workflow->getTransitionManager()->getDefaultStartTransition();

        if (null !== $defaultStartTransition) {
            $transitions[$defaultStartTransition->getName()] = $defaultStartTransition;
        }

        return $transitions;
    }

    /**
     * Check if transition is start and allowed
     *
     * @param Workflow $workflow
     * @param Transition $transition
     * @param object $entity
     *
     * @return bool
     */
    protected function isStartTransitionAllowed(Workflow $workflow, Transition $transition, $entity)
    {
        $errors = new ArrayCollection();

        return $workflow->isStartTransitionAvailable($transition, $entity, [], $errors)
               && !$transition->isHidden();
    }

    /**
     * Check if transition is available for WorkflowItem and allowed
     *
     * @param Workflow $workflow
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     *
     * @return bool
     * @internal param object $entity
     */
    protected function isTransitionAllowed(Workflow $workflow, Transition $transition, WorkflowItem $workflowItem)
    {
        $errors = new ArrayCollection();

        return $workflow->isTransitionAvailable($workflowItem, $transition, $errors)
               && !$transition->isHidden();
    }

    /**
     * @param string $permission
     * @param string $workflowName
     * @param object $entity
     *
     * @return bool
     */
    protected function isWorkflowPermissionGranted($permission, $workflowName, $entity)
    {
        $domainObject = new DomainObjectWrapper($entity, new ObjectIdentity('workflow', $workflowName));

        return $this->securityFacade->isGranted($permission, $domainObject);
    }
}
