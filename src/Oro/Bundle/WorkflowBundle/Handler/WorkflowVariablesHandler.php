<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

class WorkflowVariablesHandler extends WorkflowDefinitionHandler
{
    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowData $data
     *
     * @return WorkflowDefinition
     */
    public function updateWorkflowVariables(WorkflowDefinition $definition, WorkflowData $data)
    {
        $workflowConfig = $definition->getConfiguration();
        $variableDefinitionsConfig = $workflowConfig[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS];
        $variablesConfig = $variableDefinitionsConfig[WorkflowConfiguration::NODE_VARIABLES];

        foreach ($data as $name => $value) {
            if (!isset($variablesConfig[$name])) {
                continue;
            }
            $variablesConfig[$name]['value'] = $value;
        }

        $variableDefinitionsConfig[WorkflowConfiguration::NODE_VARIABLES] = $variablesConfig;
        $workflowConfig[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS] = $variableDefinitionsConfig;
        $definition->setConfiguration($workflowConfig);
        $this->process($definition);

        return $definition;
    }
}
