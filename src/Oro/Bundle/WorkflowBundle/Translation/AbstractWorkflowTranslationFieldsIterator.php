<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionAttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionWarningMessageTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowAttributeLabelTemplate;

abstract class AbstractWorkflowTranslationFieldsIterator extends AbstractTranslationFieldsIterator
{
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

                if (isset($transition['message'])) {
                    yield $this->makeKey(TransitionWarningMessageTemplate::class, $context) => $transition['message'];
                }

                foreach ($this->transitionAttributeFields($transition, $context) as $key => &$attrValue) {
                    yield $key => $attrValue;
                }
                unset($attrValue, $context['transition_name']);
            }
            unset($transition);
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
                if (isset($attributeConfig['options']['label'])) {
                    $context['attribute_name'] = $attributeName;
                    $key = $this->makeKey(TransitionAttributeLabelTemplate::class, $context);
                    yield $key => $attributeConfig['options']['label'];
                    unset($context['attribute_name']);
                }
            }
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
