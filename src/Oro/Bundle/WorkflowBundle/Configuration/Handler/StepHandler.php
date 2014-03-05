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
        'entity_acl',
        'allowed_transitions'
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
            } else {
                $handledStep = $this->handleStepConfiguration($configuration, $rawStep);
                $handledSteps[] = $handledStep;
                if (!empty($configuration['start_step']) && $configuration['start_step'] == $handledStep['name']) {
                    $startStepExists = true;
                }
            }
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
            if (array_key_exists('name', $transition) && in_array($transition['name'], $startTransitions)
                || in_array($key, $startTransitions)
            ) {
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
            $step['name'] = uniqid('step_', true);
        }

        if (empty($step['label'])) {
            $step['label'] = $step['name'];
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
        $transitions = array();
        if (!empty($configuration[WorkflowConfiguration::NODE_TRANSITIONS])) {
            $transitions = $configuration[WorkflowConfiguration::NODE_TRANSITIONS];
        }

        foreach ($transitions as $key => $transition) {
            if (array_key_exists('name', $transition) && $transition['name'] == $transitionName
                || $key === $transitionName
            ) {
                return true;
            }
        }

        return false;
    }
}
