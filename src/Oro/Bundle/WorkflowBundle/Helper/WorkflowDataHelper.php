<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

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
    private $securityFacade;

    /**
     * @param WorkflowManager $workflowManager
     * @param SecurityFacade $securityFacade
     */
    public function __construct(WorkflowManager $workflowManager, SecurityFacade $securityFacade)
    {
        $this->workflowManager = $workflowManager;
        $this->securityFacade = $securityFacade;
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
     * @param object $entity
     * @param Workflow $workflow
     *
     * @return array
     */
    protected function getWorkflowData($entity, Workflow $workflow)
    {
        $workflowItem = $this->workflowManager->getWorkflowItem($entity, $workflow->getName());

        $transitionData = $workflowItem
            ? $this->getAvailableTransitionsDataByWorkflowItem($workflowItem)
            : $this->getAvailableStartTransitionsData($workflow, $entity);

        $isStepsDisplayOrdered = $workflow->getDefinition()->isStepsDisplayOrdered();
        $currentStep = $workflowItem ? $workflowItem->getCurrentStep() : null;

        $helper = new WorkflowStepHelper($workflow);
        $stepManager = $workflow->getStepManager();

        if ($isStepsDisplayOrdered) {
            $steps = $stepManager->getOrderedSteps(true, true)->toArray();

            if ($workflowItem) {
                $startStepNames = array_map(
                    function (array $data) {
                        /** @var Transition $transition */
                        $transition = $data['transition'];

                        return $transition->getStepTo()->getName();
                    },
                    $this->getAvailableStartTransitionsData($workflow, $entity)
                );

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
                function (array $data) {
                    /** @var Transition $transition */
                    $transition = $data['transition'];

                    return $transition->getStepTo();
                },
                $transitionData
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
            'name' => $workflow->getName(),
            'label' => $workflow->getLabel(),
            'isStarted' => $workflowItem !== null,
            'stepsData' => [
                'isOrdered' => $isStepsDisplayOrdered,
                'steps' => $steps,
            ],
            'transitionsData' => $transitionData,
        ];
    }

    /**
     * @param Transition $transition
     *
     * @return array
     */
    protected function getTransitionData(Transition $transition)
    {
        if ($transition->isUnavailableHidden()) {
            return null;
        }

        return [
            'name' => $transition->getName(),
            'label' => $transition->getLabel(),
            'isStart' => $transition->isStart(),
            'hasForm' => $transition->hasForm(),
            'displayType' => $transition->getDisplayType(),
            'frontendOptions' => $transition->getFrontendOptions(),
        ];
    }

    /**
     * Get transitions data for view based on workflow item.
     *
     * @param WorkflowItem $workflowItem
     *
     * @return array
     */
    protected function getAvailableTransitionsDataByWorkflowItem(WorkflowItem $workflowItem)
    {
        $transitionsData = [];
        $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
        /** @var Transition $transition */
        foreach ($transitions as $transition) {
            if (!$transition->isHidden()) {
                $errors = new ArrayCollection();
                $isAllowed = $this->workflowManager->isTransitionAvailable($workflowItem, $transition, $errors);
                if ($isAllowed || !$transition->isUnavailableHidden()) {
                    $transitionsData[$transition->getName()] = [
                        'workflowItemId' => $workflowItem->getId(),
                        'transition' => $this->getTransitionData($transition),
                        'isAllowed' => $isAllowed,
                        'errors' => $errors,
                    ];
                }
            }
        }

        return $transitionsData;
    }

    /**
     * Get start transitions data for view based on workflow and entity.
     *
     * @param Workflow $workflow
     * @param object $entity
     *
     * @return array
     */
    protected function getAvailableStartTransitionsData(Workflow $workflow, $entity)
    {
        $transitionsData = [];

        $transitions = $workflow->getTransitionManager()->getStartTransitions();
        /** @var Transition $transition */
        foreach ($transitions as $transition) {
            if (!$transition->isHidden()) {
                $transitionData = $this->getStartTransitionData($workflow, $transition, $entity);
                if ($transitionData !== null) {
                    $transitionsData[$transition->getName()] = $transitionData;
                }
            }
        }

        // extra case to show start transition
        if (empty($transitionsData) && $workflow->getStepManager()->hasStartStep()) {
            $defaultStartTransition = $workflow->getTransitionManager()->getDefaultStartTransition();
            if ($defaultStartTransition) {
                $startTransitionData = $this->getStartTransitionData($workflow, $defaultStartTransition, $entity);
                if ($startTransitionData !== null) {
                    $transitionsData[$defaultStartTransition->getName()] = $startTransitionData;
                } elseif (!$this->isWorkflowPermissionGranted('PERFORM_TRANSITIONS', $workflow->getName(), $entity)) {
                    // extra case to show start transition (step name and disabled button)
                    // even if transitions performing is forbidden with ACL
                    $transitionsData[$defaultStartTransition->getName()] = [
                        'transition' => $this->getTransitionData($transition),
                        'isAllowed' => false,
                        'errors' => new ArrayCollection(),
                    ];
                }
            }
        }

        return $transitionsData;
    }

    /**
     * @param Workflow $workflow
     * @param Transition $transition
     * @param object $entity
     *
     * @return array|null
     */
    protected function getStartTransitionData(Workflow $workflow, Transition $transition, $entity)
    {
        $errors = new ArrayCollection();
        $isAllowed = $workflow->isStartTransitionAvailable($transition, $entity, [], $errors);
        if ($isAllowed || !$transition->isUnavailableHidden()) {
            return [
                'transition' => $this->getTransitionData($transition),
                'isAllowed' => $isAllowed,
                'errors' => $errors,
            ];
        }

        return null;
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
        return $this->securityFacade->isGranted(
            $permission,
            new DomainObjectWrapper($entity, new ObjectIdentity('workflow', $workflowName))
        );
    }
}
