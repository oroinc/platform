<?php

namespace Oro\Bundle\WorkflowBundle\Generator;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class ProcessConfigurationGenerator
{
    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    public function generateForScheduledTransition(WorkflowDefinition $workflowDefinition)
    {
        return [
            ProcessConfigurationProvider::NODE_DEFINITIONS => [],
            ProcessConfigurationProvider::NODE_TRIGGERS => []
        ];
    }
}
