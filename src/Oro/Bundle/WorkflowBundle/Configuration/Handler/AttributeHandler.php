<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

/**
 * Handles workflow attribute configuration normalization.
 *
 * Processes raw attribute configurations from workflow definitions, ensuring each attribute
 * has a unique name. If an attribute lacks a name, a unique identifier is automatically generated.
 * This handler is part of the workflow configuration processing pipeline that validates and
 * normalizes workflow definitions before they are used by the workflow engine.
 */
class AttributeHandler extends AbstractHandler
{
    #[\Override]
    public function handle(array $configuration)
    {
        $rawAttributes = array();
        if (!empty($configuration[WorkflowConfiguration::NODE_ATTRIBUTES])) {
            $rawAttributes = $configuration[WorkflowConfiguration::NODE_ATTRIBUTES];
        }

        $handledAttributes = array();
        foreach ($rawAttributes as $rawAttribute) {
            $handledAttributes[] = $this->handleAttributeConfiguration($rawAttribute);
        }

        $configuration[WorkflowConfiguration::NODE_ATTRIBUTES] = $handledAttributes;

        return $configuration;
    }

    /**
     * @param array $attribute
     * @return array
     */
    protected function handleAttributeConfiguration(array $attribute)
    {
        if (empty($attribute['name'])) {
            $attribute['name'] = uniqid('attribute_');
        }

        return $attribute;
    }
}
