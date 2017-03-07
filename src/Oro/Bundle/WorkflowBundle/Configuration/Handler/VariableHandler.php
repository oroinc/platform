<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class VariableHandler extends AbstractHandler
{
    /**
     * {@inheritDoc}
     */
    public function handle(array $configuration)
    {
        $rawVarDefinitions = [];
        if (!empty($configuration[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS])) {
            $rawVarDefinitions = $configuration[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS];
        }

        $handledVariables = $variables = [];
        if (isset($rawVarDefinitions[WorkflowConfiguration::NODE_VARIABLES])) {
            foreach ($rawVarDefinitions[WorkflowConfiguration::NODE_VARIABLES] as $name => $variable) {
                $variables[] = $this->handleVariableConfiguration($variable, $name);
            }
        }
        $handledVariables[WorkflowConfiguration::NODE_VARIABLES] = $variables;

        $configuration[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS] = $handledVariables;

        return $configuration;
    }

    /**
     * @param array $variable
     * @param string $name
     *
     * @return array
     */
    protected function handleVariableConfiguration(array $variable, $name)
    {
        if (empty($name)) {
            $name = uniqid('variable_', true);
        }
        $variable['name'] = $name;

        if (!isset($variable['value'])) {
            $variable['value'] = null;
        }

        return $variable;
    }
}
