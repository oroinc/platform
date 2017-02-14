<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration as WC;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

class WorkflowVariablesHandler extends WorkflowDefinitionHandler
{
    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowData       $variableVal
     */
    public function updateWorkflowVariableValues(WorkflowDefinition $definition, WorkflowData $variableVal)
    {
        $workflowCfg = $definition->getConfiguration();
        $workflowVarConfig = $workflowCfg[WC::NODE_VARIABLE_DEFINITIONS][WC::NODE_VARIABLES];

        foreach ($variableVal as $variable => $variableValue) {
            foreach ($workflowVarConfig as $variableName => $variableDefinitions) {
                if ($variableName != $variable) {
                    continue;
                }
                $workflowCfg[WC::NODE_VARIABLE_DEFINITIONS][WC::NODE_VARIABLES][$variable]['value'] = $variableValue;
            }
        }
        $definition->setConfiguration($workflowCfg);
        $this->process($definition);
    }
}
