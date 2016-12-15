<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Tools\WorkflowStepHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class WorkflowDataProvider
{
    /** @var TransitionDataProvider */
    private $transitionDataProvider;

    /** @var WorkflowManager */
    private $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     * @param TransitionDataProvider $transitionDataProvider
     */
    public function __construct(WorkflowManager $workflowManager, TransitionDataProvider $transitionDataProvider)
    {
        $this->transitionDataProvider = $transitionDataProvider;
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param object $entity
     * @param Workflow $workflow
     * @param bool $showDisabled
     *
     * @return array
     */
    public function getWorkflowData($entity, Workflow $workflow, $showDisabled)
    {
        $workflowItem = $this->workflowManager->getWorkflowItem($entity, $workflow->getName());

        $transitionData = $workflowItem
            ? $this->transitionDataProvider->getAvailableTransitionsDataByWorkflowItem($workflowItem)
            : $this->transitionDataProvider->getAvailableStartTransitionsData($workflow, $entity, $showDisabled);

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
                    $this->transitionDataProvider->getAvailableStartTransitionsData($workflow, $entity, $showDisabled)
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
                    'possibleStepsCount' => count($helper->getStepsAfter($step))
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
                'steps' => $steps
            ],
            'transitionsData' => $transitionData
        ];
    }
}
