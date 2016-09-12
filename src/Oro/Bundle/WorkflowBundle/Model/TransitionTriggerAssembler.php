<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TransitionTriggerAssembler
{
    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return bool
     */
    public function hasTransitions(WorkflowDefinition $workflowDefinition)
    {
        $config = $workflowDefinition->getConfiguration();

        foreach ($config[WorkflowConfiguration::NODE_TRANSITIONS] as $transition) {
            if (!empty($transition['triggers'])) {
                return true;
            }
        }

        return false;
    }

    public function assembleTriggers(WorkflowDefinition $workflowDefinition)
    {
        $config = $workflowDefinition->getConfiguration();

        $triggers = [];

        foreach ($config[WorkflowConfiguration::NODE_TRANSITIONS] as $transitionConfig) {
            if (!empty($transitionConfig['triggers'])) {
                foreach ($transitionConfig['triggers'] as $triggerConfig) {
                    if($transitionConfig['cron']){

                    }
                }
            }
        }
    }
}
