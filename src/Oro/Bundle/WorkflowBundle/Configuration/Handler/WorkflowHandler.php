<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

class WorkflowHandler implements ConfigurationHandlerInterface
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
        $configuration = $this->handleWorkflowFields($configuration);

        foreach ($this->handlers as $handler) {
            $configuration = $handler->handle($configuration);
        }

        return $configuration;
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function handleWorkflowFields(array $configuration)
    {
        if (empty($configuration['name'])) {
            $configuration['name'] = uniqid('workflow_', true);
        }

        if (empty($configuration['label'])) {
            $configuration['label'] = $configuration['name'];
        }

        if (!empty($configuration['entity']) && !class_exists($configuration['entity'])) {
            unset($configuration['entity']);
        }

        return $configuration;
    }
}
