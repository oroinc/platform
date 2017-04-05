<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Tools\WorkflowStepHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;

class WorkflowDataProvider
{
    /** @var TransitionDataProvider */
    private $transitionDataProvider;

    /** @var WorkflowManagerRegistry */
    private $workflowManagerRegistry;

    /**
     * @param WorkflowManagerRegistry $workflowManagerRegistry
     * @param TransitionDataProvider $transitionDataProvider
     */
    public function __construct(
        WorkflowManagerRegistry $workflowManagerRegistry,
        TransitionDataProvider $transitionDataProvider
    ) {
        $this->transitionDataProvider = $transitionDataProvider;
        $this->workflowManagerRegistry = $workflowManagerRegistry;
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
        $workflowItem = $this->getWorkflowManager()->getWorkflowItem($entity, $workflow->getName());

        $transitionData = [];

        if ($this->isAvailableWorkflow($workflow, $entity)) {
            $transitionData = $workflowItem
                ? $this->transitionDataProvider->getAvailableTransitionsDataByWorkflowItem($workflowItem)
                : $this->transitionDataProvider->getAvailableStartTransitionsData($workflow, $entity, $showDisabled);
        }

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
                    'possibleStepsCount' => count($helper->getStepsAfter($step)),
                    'final' => $step->isFinal(),
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
            'transitionsData' => $transitionData,
        ];
    }

    /**
     * @param Workflow $workflow
     * @param object $entity
     *
     * @return bool
     */
    protected function isAvailableWorkflow(Workflow $workflow, $entity)
    {
        $workflows = array_map(
            function (Workflow $workflow) {
                return $workflow->getName();
            },
            $this->getWorkflowManager('default')->getApplicableWorkflows($entity)
        );

        return in_array($workflow->getName(), $workflows, true);
    }

    /**
     * @param string|null $type
     * @return WorkflowManager
     */
    protected function getWorkflowManager($type = null)
    {
        return $this->workflowManagerRegistry->getManager($type);
    }
}
