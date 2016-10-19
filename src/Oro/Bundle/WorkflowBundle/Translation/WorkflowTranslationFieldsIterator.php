<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionAttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionWarningMessageTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowAttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowLabelTemplate;

class WorkflowTranslationFieldsIterator
{
    /** @var TranslationKeyTemplateInterface[] */
    private $templateInstances = [];

    /** @var TranslationKeyGenerator */
    private $keyGenerator;

    /**
     * @param TranslationKeyGenerator $keyGenerator
     */
    public function __construct(TranslationKeyGenerator $keyGenerator)
    {
        $this->keyGenerator = $keyGenerator;
    }

    /**
     * Iterates over translatable fields in configuration with possibility to modify values passed by reference.
     * Iteration keys are generated translationKey for current field.
     * Iteration values are current values under the field.
     * @param string $workflowName
     * @param array $configuration
     * @return \Generator|array($translationKey => &$fieldValue)
     * @throws \InvalidArgumentException
     */
    public function &iterateConfigTranslationFields($workflowName, array &$configuration)
    {
        $context = new \ArrayObject(['workflow_name' => $workflowName]);

        yield $this->makeKey(WorkflowLabelTemplate::class, $context) => $configuration['label'];

        foreach ($this->transitionFields($configuration, $context) as $translationKey => &$transitionFieldValue) {
            yield $translationKey => $transitionFieldValue;
        }
        unset($transitionFieldValue);

        if ($this->hasArrayNode(WorkflowConfiguration::NODE_STEPS, $configuration)) {
            foreach ($configuration[WorkflowConfiguration::NODE_STEPS] as $stepKey => &$rawStep) {
                $context['step_name'] = $this->resolveName($rawStep, $stepKey);
                yield $this->makeKey(StepLabelTemplate::class, $context) => $rawStep['label'];
                unset($context['step_name']);
            }
            unset($rawStep);
        }

        foreach ($this->attributeFields($configuration, $context) as $translationKey => &$attributeFieldValue) {
            yield $translationKey => $attributeFieldValue;
        }
        unset($attributeFieldValue);
    }

    /**
     * @param array $configuration
     * @param \ArrayObject $context
     * @return \Generator
     * @throws \InvalidArgumentException
     */
    private function &attributeFields(array &$configuration, \ArrayObject $context)
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
     * @throws \InvalidArgumentException
     */
    private function &transitionFields(array &$configuration, \ArrayObject $context)
    {
        if ($this->hasArrayNode(WorkflowConfiguration::NODE_TRANSITIONS, $configuration)) {
            /** @var array[] $configuration */
            foreach ($configuration[WorkflowConfiguration::NODE_TRANSITIONS] as $transitionKey => &$rawTransition) {
                $context['transition_name'] = $this->resolveName($rawTransition, $transitionKey);
                yield $this->makeKey(TransitionLabelTemplate::class, $context) => $rawTransition['label'];
                yield $this->makeKey(TransitionWarningMessageTemplate::class, $context) => $rawTransition['message'];

                foreach ($this->transitionAttributeFields($rawTransition, $context) as $key => &$attrValue) {
                    yield $key => $attrValue;
                }
                unset($attrValue, $context['transition_name']);
            }
            unset($rawTransition);
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
     * @param array $node
     * @param $fallBackName
     * @return string
     * @internal param string $expectedKey
     */
    private function resolveName(array &$node, $fallBackName)
    {
        return (string)!empty($node['name']) ? $node['name'] : $fallBackName;
    }

    /**
     * Iterates over translatable fields in WorkflowDefinition.
     * Iteration keys are generated translationKey for current field.
     * Iteration values are current values under the field.
     * @param WorkflowDefinition $definition
     * @return \Generator|array($translationKey => $fieldValue)
     * @throws \InvalidArgumentException
     */
    public function iterateWorkflowDefinition(WorkflowDefinition $definition)
    {
        $context = new \ArrayObject(['workflow_name' => $definition->getName()]);
        $translationKey = $this->makeKey(WorkflowLabelTemplate::class, $context);
        yield  $translationKey => $definition->getLabel();

        $configuration = $definition->getConfiguration();

        foreach ($this->transitionFields($configuration, $context) as $translationKey => &$transitionFieldValue) {
            yield $translationKey => $transitionFieldValue;
        }
        unset($transitionFieldValue);

        foreach ($definition->getSteps() as $step) {
            $context['step_name'] = $step->getName();
            $translationKey = $this->makeKey(StepLabelTemplate::class, $context);
            yield $translationKey => $step->getLabel();
            unset($context['step_name']);
        }

        foreach ($this->attributeFields($configuration, $context) as $translationKey => &$attributeFieldValue) {
            yield $translationKey => $attributeFieldValue;
        }
        unset($attributeFieldValue);
    }

    /**
     * @param string $key
     * @param array $configuration
     * @return bool
     */
    private function hasArrayNode($key, array &$configuration)
    {
        return array_key_exists($key, $configuration) && is_array($configuration[$key]);
    }

    /**
     * @param string $templateClass
     * @param \ArrayObject $context
     * @return string
     * @throws \InvalidArgumentException
     */
    private function makeKey($templateClass, \ArrayObject $context)
    {
        return $this->keyGenerator->generate($this->makeSource($templateClass, $context));
    }

    /**
     * @param $templateClass
     * @param \ArrayObject $context
     * @return TranslationKeySource
     * @throws \InvalidArgumentException
     */
    private function makeSource($templateClass, \ArrayObject $context)
    {
        return new TranslationKeySource($this->getTemplate($templateClass), $context->getArrayCopy());
    }

    /**
     * @param $templateClass
     * @return TranslationKeyTemplateInterface
     * @throws \InvalidArgumentException
     */
    private function getTemplate($templateClass)
    {
        if (array_key_exists($templateClass, $this->templateInstances)) {
            return $this->templateInstances[$templateClass];
        }

        if (!is_a($templateClass, TranslationKeyTemplateInterface::class, true)) {
            throw new \InvalidArgumentException(
                sprintf('Template class must implement %s', TranslationKeyTemplateInterface::class)
            );
        }

        return $this->templateInstances[$templateClass] = new $templateClass;
    }
}
