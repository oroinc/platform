<?php

namespace Oro\Bundle\WorkflowBundle\Model\Tools;

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
     * @return array|Step[]
     */
    public function getStepsAfter(Step $step)
    {
        $allowedTransitionStepsTo = array_map(
            function ($allowedTransition) {
                return $this->getTransitionManager()
                    ->getTransition($allowedTransition)
                    ->getStepTo()
                    ->getName();
            },
            $step->getAllowedTransitions()
        );

        $steps = array_values(
            array_intersect($allowedTransitionStepsTo, $this->getStepsWithMoreOrder($step))
        );

        return array_map(
            function ($stepName) {
                return $this->getStepManager()->getStep($stepName);
            },
            $steps
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
