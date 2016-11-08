<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

class WorkflowHandler extends AbstractHandler
{
    /**
     * @var ConfigurationHandlerInterface[]
     */
    protected $handlers = array();

    /**
     * @param ConfigurationHandlerInterface $handler
     */
    public function addHandler(ConfigurationHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * {@inheritDoc}
     */
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
