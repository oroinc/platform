<?php

namespace Oro\Bundle\WorkflowBundle\Generator;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class ProcessConfigurationGenerator
{
    /**
     * @param Workflow $workflow
     * @return array
     */
    public function generateForScheduledTransition(Workflow $workflow)
    {
        return [
            [
                ProcessConfigurationProvider::NODE_DEFINITIONS => [],
                ProcessConfigurationProvider::NODE_TRIGGERS => []
            ]
        ];
    }
}
