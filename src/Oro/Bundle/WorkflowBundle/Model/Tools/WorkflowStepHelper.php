<?php

namespace Oro\Bundle\WorkflowBundle\Model\Tools;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class WorkflowStepHelper
{
    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @param Workflow $workflow
     */
    public function __construct(Workflow $workflow)
    {
        $this->workflow = $workflow;
    }

    /**
     * @param Step $step
     * @param bool $withTree
     * @param bool $onlyNames
     * @return array|Step[]
     */
    public function getStepsAfter(Step $step, $withTree = false, $onlyNames = false)
    {
        $allowedTransitionStepsTo = array_map(
            function ($allowedTransition) {
                $transition = $this->getTransitionManager()->getTransition($allowedTransition);

                return $transition ? $transition->getStepTo()->getName() : null;
            },
            $step->getAllowedTransitions()
        );

        $steps = array_values(
            array_intersect($allowedTransitionStepsTo, $this->getStepsWithMoreOrder($step))
        );

        if (!$onlyNames) {
            $steps = array_map(
                function ($stepName) {
                    return $this->getStepManager()->getStep($stepName);
                },
                $steps
            );
        }

        if ($withTree) {
            foreach ($steps as $nextStep) {
                if ($onlyNames) {
                    $nextStep = $this->getStepManager()->getStep($nextStep);
                }

                $steps = array_merge($steps, $this->getStepsAfter($nextStep, true, $onlyNames));
            }
        }

        return $steps;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @param array $startStepNames
     * @param bool $onlyNames
     * @return array|Step[]
     */
    public function getStepsBefore(WorkflowItem $workflowItem, array $startStepNames, $onlyNames = false)
    {
        $records = array_reverse($workflowItem->getTransitionRecords()->toArray());
        $path = [];

        /** @var WorkflowTransitionRecord[] $records */
        foreach ($records as $record) {
            $path[] = $record->getStepTo();
            if (in_array($record->getStepTo()->getName(), $startStepNames, true)) {
                break;
            }
        }

        $stepManager = $this->getStepManager();

        return array_map(
            function (WorkflowStep $step) use ($stepManager, $onlyNames) {
                return $onlyNames ? $step->getName() : $stepManager->getStep($step->getName());
            },
            array_reverse($path)
        );
    }

    /**
     * @param Step $step
     * @return array|string[]
     */
    protected function getStepsWithMoreOrder(Step $step)
    {
        $steps = $this->workflow->getStepManager()
            ->getOrderedSteps()
            ->filter(
                function (Step $item) use ($step) {
                    return $item->getOrder() > $step->getOrder();
                }
            );

        return array_map(
            function (Step $step) {
                return $step->getName();
            },
            $steps->toArray()
        );
    }

    /**
     * @return TransitionManager
     */
    protected function getTransitionManager()
    {
        return $this->workflow->getTransitionManager();
    }

    /**
     * @return StepManager
     */
    protected function getStepManager()
    {
        return $this->workflow->getStepManager();
    }
}
