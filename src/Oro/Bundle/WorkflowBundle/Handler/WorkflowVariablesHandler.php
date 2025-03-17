<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

/**
 * The handler for updating workflow variables.
 */
class WorkflowVariablesHandler
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    public function updateWorkflowVariables(WorkflowDefinition $definition, WorkflowData $data): WorkflowDefinition
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

        $em = $this->doctrine->getManagerForClass(WorkflowDefinition::class);
        $em->persist($definition);
        $em->beginTransaction();
        try {
            $em->flush();
            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            throw $exception;
        }

        return $definition;
    }
}
