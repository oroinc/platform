<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class WorkflowHandler extends AbstractHandler
{
    /**
     * @var array
     */
    protected $workflowKeys = array(
        'name',
        'label',
        'entity',
        'is_system',
        'start_step',
        'entity_attribute',
        'steps_display_ordered',
        WorkflowConfiguration::NODE_STEPS,
        WorkflowConfiguration::NODE_ATTRIBUTES,
        WorkflowConfiguration::NODE_TRANSITIONS,
        WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
        WorkflowConfiguration::NODE_ENTITY_RESTRICTIONS,
    );

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

        if (empty($workflow['label'])) {
            $workflow['label'] = $workflow['name'];
        }

        if (!empty($workflow['entity']) && !class_exists($workflow['entity'])) {
            unset($workflow['entity']);
        }

        return $this->filterKeys($workflow, $this->workflowKeys);
    }
}
