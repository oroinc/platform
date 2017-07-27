<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Translation\TranslationFieldsIteratorInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslationFieldsIteratorTrait;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionAttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionButtonLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionButtonTitleTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionWarningMessageTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowAttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowVariableFormOptionTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowVariableLabelTemplate;

abstract class AbstractWorkflowTranslationFieldsIterator implements TranslationFieldsIteratorInterface
{
    use TranslationFieldsIteratorTrait;

    /**
     * @param array $configuration
     * @param \ArrayObject $context
     * @return \Generator
     * @throws \InvalidArgumentException
     */
    protected function &attributeFields(array &$configuration, \ArrayObject $context)
    {
        if ($this->hasArrayNode(WorkflowConfiguration::NODE_ATTRIBUTES, $configuration)) {
            foreach ($configuration[WorkflowConfiguration::NODE_ATTRIBUTES] as $attributeKey => &$rawAttribute) {
                $context['attribute_name'] = $this->resolveName($rawAttribute, $attributeKey);
                yield $this->makeKey(WorkflowAttributeLabelTemplate::class, $context) => $rawAttribute['label'];
                unset($context['attribute_name']);
            }
            unset($rawAttribute);
        }
    }

    /**
     * @param array $configuration
     * @param \ArrayObject $context
     * @return \Generator
     */
    protected function &stepFields(array &$configuration, \ArrayObject $context)
    {
        if ($this->hasArrayNode(WorkflowConfiguration::NODE_STEPS, $configuration)) {
            foreach ($configuration[WorkflowConfiguration::NODE_STEPS] as $stepKey => &$rawStep) {
                $context['step_name'] = $this->resolveName($rawStep, $stepKey);
                yield $this->makeKey(StepLabelTemplate::class, $context) => $rawStep['label'];
                unset($context['step_name']);
            }
            unset($rawStep);
        }
    }

    /**
     * @param array $configuration
     * @param \ArrayObject $context
     * @return \Generator
     * @throws \InvalidArgumentException
     */
    protected function &transitionFields(array &$configuration, \ArrayObject $context)
    {
        if ($this->hasArrayNode(WorkflowConfiguration::NODE_TRANSITIONS, $configuration)) {
            /** @var array[] $configuration */
            foreach ($configuration[WorkflowConfiguration::NODE_TRANSITIONS] as $transitionKey => &$transition) {
                $context['transition_name'] = $this->resolveName($transition, $transitionKey);
                yield $this->makeKey(TransitionLabelTemplate::class, $context) => $transition['label'];
                yield $this->makeKey(TransitionButtonLabelTemplate::class, $context) => $transition['button_label'];
                yield $this->makeKey(TransitionButtonTitleTemplate::class, $context) => $transition['button_title'];
                yield $this->makeKey(TransitionWarningMessageTemplate::class, $context) => $transition['message'];

                $attributeFields = $this->transitionAttributeFields($transition, $context);
                foreach ($attributeFields as $key => &$attrValue) {
                    yield $key => $attrValue;
                }
                unset($attrValue, $context['transition_name']);
            }
            unset($transition);
        }
    }

    /**
     * @param array $configuration
     * @param \ArrayObject $context
     * @return \Generator
     * @throws \InvalidArgumentException
     */
    protected function &variableFields(array &$configuration, \ArrayObject $context)
    {
        if ($this->hasArrayNode(WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS, $configuration)) {
            $definitions = &$configuration[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS];

            if ($this->hasArrayNode(WorkflowConfiguration::NODE_VARIABLES, $definitions)) {
                foreach ($definitions[WorkflowConfiguration::NODE_VARIABLES] as $variableKey => &$rawVariable) {
                    $context['variable_name'] = $this->resolveName($rawVariable, $variableKey);
                    yield $this->makeKey(WorkflowVariableLabelTemplate::class, $context) => $rawVariable['label'];

                    $formFields = $this->variableFormFields($rawVariable, $context);
                    foreach ($formFields as $key => &$formField) {
                        yield $key => $formField;
                    }

                    unset($context['variable_name'], $formField);
                }
                unset($rawVariable);
            }
        }
    }

    /**
     * @param array $variableConfig
     * @param \ArrayObject $context
     * @return \Generator
     */
    private function &variableFormFields(array &$variableConfig, \ArrayObject $context)
    {
        if ($this->hasArrayNode('options', $variableConfig)) {
            $options = &$variableConfig['options'];
            if ($this->hasArrayNode('form_options', $options)) {
                if (isset($options['form_options']['tooltip'])) {
                    $context['option_name'] = 'tooltip';
                    $key = $this->makeKey(WorkflowVariableFormOptionTemplate::class, $context);

                    yield $key => $options['form_options']['tooltip'];
                    unset($context['option_name']);
                }
            }
            unset($options);
        }
    }

    /**
     * @param array $transitionConfig
     * @param \ArrayObject $context
     * @return \Generator
     */
    private function &transitionAttributeFields(array &$transitionConfig, \ArrayObject $context)
    {
        if ($this->hasArrayNode('form_options', $transitionConfig)
            && $this->hasArrayNode('attribute_fields', $transitionConfig['form_options'])
        ) {
            foreach ($transitionConfig['form_options']['attribute_fields'] as $attributeName => &$attributeConfig) {
                $context['attribute_name'] = $attributeName;
                $key = $this->makeKey(TransitionAttributeLabelTemplate::class, $context);

                if (isset($attributeConfig['label'])) {
                    $attributeConfig['options']['label'] = $attributeConfig['label'];
                    unset($attributeConfig['label']);
                }

                yield $key => $attributeConfig['options']['label'];
                unset($context['attribute_name']);
            }
            unset($attributeConfig);
        }
    }

    /**
     * @param string $key
     * @param array $configuration
     * @return bool
     */
    protected function hasArrayNode($key, array &$configuration)
    {
        return array_key_exists($key, $configuration) && is_array($configuration[$key]);
    }

    /**
     * @param array $node
     * @param $fallBackName
     * @return string
     * @internal param string $expectedKey
     */
    private function resolveName(array &$node, $fallBackName)
    {
        return (string)!empty($node['name']) ? $node['name'] : $fallBackName;
    }
}
