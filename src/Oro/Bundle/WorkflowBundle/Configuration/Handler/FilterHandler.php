<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

/**
 * Finalize incoming workflow configuration by clearing unsupported nodes
 */
class FilterHandler implements ConfigurationHandlerInterface
{
    /** @var array */
    protected static $stepKeys = [
        'name',
        'order',
        'is_final',
        '_is_start',
        'entity_acl',
        'allowed_transitions',
        'position'
    ];

    /** @var array */
    protected static $transitionKeys = [
        'name',
        'step_to',
        'is_start',
        'is_hidden',
        'is_unavailable_hidden',
        'acl_resource',
        'acl_message',
        'transition_definition',
        'frontend_options',
        'form_type',
        'display_type',
        'form_options'
    ];

    /** @var array */
    protected static $transitionDefinitionKeys = [
        'name',
        'preactions',
        'preconditions',
        'actions'
    ];

    /** @var array */
    protected static $attributeKeys = [
        'name',
        'type',
        'entity_acl',
        'property_path',
        'options'
    ];

    /** @var array */
    protected static $workflowKeys = [
        'name',
        'entity',
        'is_system',
        'start_step',
        'entity_attribute',
        'steps_display_ordered',
        'priority',
        WorkflowConfiguration::NODE_SCOPES,
        WorkflowConfiguration::NODE_STEPS,
        WorkflowConfiguration::NODE_ATTRIBUTES,
        WorkflowConfiguration::NODE_TRANSITIONS,
        WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
        WorkflowConfiguration::NODE_ENTITY_RESTRICTIONS,
        WorkflowConfiguration::NODE_EXCLUSIVE_ACTIVE_GROUPS,
        WorkflowConfiguration::NODE_EXCLUSIVE_RECORD_GROUPS
    ];

    /**
     * @param array $configuration
     * @return array
     */
    public function handle(array $configuration)
    {
        $this->filterConfigNode(WorkflowConfiguration::NODE_ATTRIBUTES, self::$attributeKeys, $configuration);
        $this->filterConfigNode(WorkflowConfiguration::NODE_TRANSITIONS, self::$transitionKeys, $configuration);
        $this->filterConfigNode(WorkflowConfiguration::NODE_STEPS, self::$stepKeys, $configuration);
        $this->filterConfigNode(
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
            self::$transitionDefinitionKeys,
            $configuration
        );

        return $this->filterKeys($configuration, self::$workflowKeys);
    }

    /**
     * @param string $nodeKey
     * @param array $keys
     * @param array $configuration
     */
    private function filterConfigNode($nodeKey, array $keys, array &$configuration)
    {
        if (!empty($configuration[$nodeKey]) && is_array($configuration[$nodeKey])) {
            $filteredAttributes = [];
            foreach ($configuration[$nodeKey] as $rawAttribute) {
                $filteredAttributes[] = $this->filterKeys($rawAttribute, $keys);
            }
            $configuration[$nodeKey] = $filteredAttributes;
        }
    }

    /**
     * @param array $configuration
     * @param array $keys
     * @return array
     */
    protected function filterKeys(array $configuration, array $keys)
    {
        return array_intersect_key($configuration, array_flip($keys));
    }
}
