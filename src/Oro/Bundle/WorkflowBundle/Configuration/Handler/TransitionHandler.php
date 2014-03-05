<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class TransitionHandler extends AbstractHandler
{
    /**
     * @var array
     */
    protected $transitionKeys = array(
        'name',
        'label',
        'step_to',
        'is_start',
        'is_hidden',
        'is_unavailable_hidden',
        'acl_resource',
        'acl_message',
        'message',
        'transition_definition',
        'frontend_options',
        'form_type',
        'display_type',
        'form_options'
    );

    /**
     * {@inheritDoc}
     */
    public function handle(array $configuration)
    {
        $rawTransitions = array();
        if (!empty($configuration[WorkflowConfiguration::NODE_TRANSITIONS])) {
            $rawTransitions = $configuration[WorkflowConfiguration::NODE_TRANSITIONS];
        }

        $handledTransitions = array();
        foreach ($rawTransitions as $rawTransition) {
            if (!empty($rawTransition['step_to']) && $this->hasStep($configuration, $rawTransition['step_to'])) {
                $handledTransition = $this->handleTransitionConfiguration($configuration, $rawTransition);
                $handledTransitions[] = $handledTransition;

                $transitionDefinition = $handledTransition['transition_definition'];
                if (!$this->hasTransitionDefinition($configuration, $transitionDefinition)) {
                    $configuration[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS][] = array(
                        'name' => $transitionDefinition
                    );
                }
            }
        }

        $configuration[WorkflowConfiguration::NODE_TRANSITIONS] = $handledTransitions;

        return $configuration;
    }

    /**
     * @param array $configuration
     * @param array $transition
     * @return array
     */
    protected function handleTransitionConfiguration(array $configuration, array $transition)
    {
        if (empty($transition['name'])) {
            $transition['name'] = uniqid('transition_', true);
        }

        if (empty($transition['label'])) {
            $transition['label'] = $transition['name'];
        }

        if (empty($transition['transition_definition'])) {
            $transition['transition_definition'] = uniqid('transition_definition_', true);
        }

        if (!empty($transition['form_options'])) {
            $transition['form_options'] = $this->handleFormOptions($configuration, $transition['form_options']);
        }

        $transition = $this->filterKeys($transition, $this->transitionKeys);

        return $transition;
    }

    /**
     * @param array $configuration
     * @param array $formOptions
     * @return array
     */
    protected function handleFormOptions(array $configuration, array $formOptions)
    {
        $attributeFields = array();
        if (!empty($formOptions['attribute_fields'])) {
            $attributeFields = $formOptions['attribute_fields'];
        }

        $handledAttributeFields = array();
        foreach ($attributeFields as $name => $parameters) {
            if ($this->hasAttribute($configuration, $name)) {
                if (!empty($parameters['options']['required'])) {
                    $parameters['options'] = $this->addNotBlankConstraint($parameters['options']);
                }
                $handledAttributeFields[$name] = $parameters;
            }
        }

        $formOptions['attribute_fields'] = $handledAttributeFields;

        return $formOptions;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function addNotBlankConstraint(array $options)
    {
        $constraints = array();
        if (!empty($options['constraints'])) {
            $constraints = $options['constraints'];
        }

        $constraintTypes = array();
        foreach ($constraints as $constraint) {
            $constraintTypes = array_merge($constraintTypes, array_keys($constraint));
        }

        if (!in_array('NotBlank', $constraintTypes)) {
            $options['constraints'][] = array('NotBlank' => null);
        }

        return $options;
    }

    /**
     * @param array $configuration
     * @param string $stepName
     * @return bool
     */
    protected function hasStep(array $configuration, $stepName)
    {
        return $this->hasEntityInGroup($configuration, WorkflowConfiguration::NODE_STEPS, $stepName);
    }

    /**
     * @param array $configuration
     * @param string $attributeName
     * @return bool
     */
    protected function hasAttribute(array $configuration, $attributeName)
    {
        return $this->hasEntityInGroup($configuration, WorkflowConfiguration::NODE_ATTRIBUTES, $attributeName);
    }

    /**
     * @param array $configuration
     * @param string $transitionDefinitionName
     * @return bool
     */
    protected function hasTransitionDefinition(array $configuration, $transitionDefinitionName)
    {
        return $this->hasEntityInGroup(
            $configuration,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
            $transitionDefinitionName
        );
    }
}
