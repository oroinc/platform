<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class StepHandler extends AbstractHandler
{
    /**
     * @var array
     */
    protected $stepKeys = array(
        'name',
        'label',
        'order',
        'is_final',
        '_is_start',
        'entity_acl',
        'allowed_transitions',
        'position'
    );

    /**
     * {@inheritDoc}
     */
    public function handle(array $configuration)
    {
        $rawSteps = array();
        if (!empty($configuration[WorkflowConfiguration::NODE_STEPS])) {
            $rawSteps = $configuration[WorkflowConfiguration::NODE_STEPS];
        }

        $handledSteps = array();
        $startStepExists = false;
        foreach ($rawSteps as $rawStep) {
            if (!empty($rawStep['_is_start'])) {
                $configuration = $this->processStartingPoint($configuration, $rawStep);
                $handledStep = $this->handleStepConfiguration($configuration, $rawStep);
            } else {
                $handledStep = $this->handleStepConfiguration($configuration, $rawStep);
                if (!empty($configuration['start_step']) && $configuration['start_step'] == $handledStep['name']) {
                    $startStepExists = true;
                }
            }
            $handledSteps[] = $handledStep;
        }

        $configuration[WorkflowConfiguration::NODE_STEPS] = $handledSteps;
        if (!$startStepExists) {
            $configuration['start_step'] = null;
        }

        return $configuration;
    }

    /**
     * @param array $configuration
     * @param array $startingPoint
     * @return array
     */
    protected function processStartingPoint(array $configuration, array $startingPoint)
    {
        $startTransitions = array();
        if (!empty($startingPoint['allowed_transitions'])) {
            $startTransitions = $startingPoint['allowed_transitions'];
        }

        $transitions = array();
        if (!empty($configuration[WorkflowConfiguration::NODE_TRANSITIONS])) {
            $transitions = $configuration[WorkflowConfiguration::NODE_TRANSITIONS];
        }

        // set is_start flag for transitions
        foreach ($transitions as $key => $transition) {
            if (!empty($transition['name']) && in_array($transition['name'], $startTransitions)) {
                $transitions[$key]['is_start'] = true;
            } else {
                $transitions[$key]['is_start'] = false;
            }
        }

        $configuration[WorkflowConfiguration::NODE_TRANSITIONS] = $transitions;

        return $configuration;
    }

    /**
     * @param array $configuration
     * @param array $step
     * @return array
     */
    protected function handleStepConfiguration(array $configuration, array $step)
    {
        if (empty($step['name'])) {
            $step['name'] = uniqid('step_');
        }

        if (empty($step['label'])) {
            $step['label'] = $step['name'];
        }

        if (empty($step['_is_start'])) {
            $step['_is_start'] = false;
        }

        if (empty($step['allowed_transitions'])) {
            $step['allowed_transitions'] = array();
        }
        foreach ($step['allowed_transitions'] as $key => $transition) {
            if (!$this->hasTransition($configuration, $transition)) {
                unset($step['allowed_transitions'][$key]);
            }
        }

        return $this->filterKeys($step, $this->stepKeys);
    }

    /**
     * @param array $configuration
     * @param string $transitionName
     * @return bool
     */
    protected function hasTransition(array $configuration, $transitionName)
    {
        return $this->hasEntityInGroup($configuration, WorkflowConfiguration::NODE_TRANSITIONS, $transitionName);
    }
}
