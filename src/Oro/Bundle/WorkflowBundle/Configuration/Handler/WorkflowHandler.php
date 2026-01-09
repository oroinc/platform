<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

/**
 * Orchestrates workflow configuration processing through a chain of specialized handlers.
 *
 * Acts as a composite handler that coordinates the normalization of workflow configurations by delegating
 * to specialized handlers for attributes, steps, transitions, variables, and other workflow components.
 * Performs initial workflow-level validation such as ensuring unique workflow names and validating entity
 * class references. This handler is the main entry point for processing complete workflow definitions and
 * ensures all workflow components are properly normalized before the workflow is registered with the engine.
 */
class WorkflowHandler extends AbstractHandler
{
    /**
     * @var ConfigurationHandlerInterface[]
     */
    protected $handlers = array();

    public function addHandler(ConfigurationHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    #[\Override]
    public function handle(array $configuration)
    {
        $configuration = $this->handleWorkflowConfiguration($configuration);

        foreach ($this->handlers as $handler) {
            $configuration = $handler->handle($configuration);
        }

        return $configuration;
    }

    /**
     * @param array $workflow
     * @return array
     */
    protected function handleWorkflowConfiguration(array $workflow)
    {
        if (empty($workflow['name'])) {
            $workflow['name'] = uniqid('workflow_');
        }

        if (!empty($workflow['entity']) && !class_exists($workflow['entity'])) {
            unset($workflow['entity']);
        }

        return $workflow;
    }
}
