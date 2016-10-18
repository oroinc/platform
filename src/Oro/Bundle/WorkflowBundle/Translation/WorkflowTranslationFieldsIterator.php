<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepLabelTemplate;
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
     * @return array|\Generator ($translationKey => &$fieldValue)
     * @throws \InvalidArgumentException
     */
    public function &iterateConfigTranslationFields($workflowName, array &$configuration)
    {
        $data = ['workflow_name' => $workflowName];

        yield $this->makeKey(WorkflowLabelTemplate::class, $data) => $configuration['label'];

        if ($this->hasArrayNode(WorkflowConfiguration::NODE_TRANSITIONS, $configuration)) {
            foreach ($configuration[WorkflowConfiguration::NODE_TRANSITIONS] as $transitionKey => &$rawTransition) {
                $data = [
                    'workflow_name' => $workflowName,
                    'transition_name' => $this->resolveName($rawTransition, $transitionKey)
                ];
                yield $this->makeKey(TransitionLabelTemplate::class, $data) => $rawTransition['label'];
                yield $this->makeKey(TransitionWarningMessageTemplate::class, $data) => $rawTransition['message'];
            }
            unset($rawTransition);
        }

        if ($this->hasArrayNode(WorkflowConfiguration::NODE_STEPS, $configuration)) {
            foreach ($configuration[WorkflowConfiguration::NODE_STEPS] as $stepKey => &$rawStep) {
                $data = [
                    'workflow_name' => $workflowName,
                    'step_name' => $this->resolveName($rawStep, $stepKey)
                ];
                yield $this->makeKey(StepLabelTemplate::class, $data) => $rawStep['label'];
            }
            unset($rawStep);
        }

        if ($this->hasArrayNode(WorkflowConfiguration::NODE_ATTRIBUTES, $configuration)) {
            foreach ($configuration[WorkflowConfiguration::NODE_ATTRIBUTES] as $attributeKey => &$rawAttribute) {
                $data = [
                    'workflow_name' => $workflowName,
                    'attribute_name' => $this->resolveName($rawAttribute, $attributeKey)
                ];
                yield $this->makeKey(WorkflowAttributeLabelTemplate::class, $data) => $rawAttribute['label'];
            }
            unset($rawAttribute);
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
        return (string) !empty($node['name']) ? $node['name'] : $fallBackName;
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
        $workflowName = $definition->getName();

        $key = $this->makeKey(WorkflowLabelTemplate::class, ['workflow_name' => $workflowName]);
        yield  $key => $definition->getLabel();

        $configuration = $definition->getConfiguration();

        if ($this->hasArrayNode(WorkflowConfiguration::NODE_TRANSITIONS, $configuration)) {
            foreach ($configuration[WorkflowConfiguration::NODE_TRANSITIONS] as $transitionKey => $transitionConfig) {
                $data = [
                    'workflow_name' => $workflowName,
                    'transition_name' => $this->resolveName($rawTransition, $transitionKey)
                ];
                yield $this->makeKey(TransitionLabelTemplate::class, $data) => $transitionConfig['label'];
                yield $this->makeKey(TransitionWarningMessageTemplate::class, $data) => $transitionConfig['message'];
            }
        }

        foreach ($definition->getSteps() as $step) {
            $key = $this->makeKey(
                StepLabelTemplate::class,
                ['workflow_name' => $workflowName, 'step_name' => $step->getName()]
            );
            yield $key => $step->getLabel();
        }

        if ($this->hasArrayNode(WorkflowConfiguration::NODE_ATTRIBUTES, $configuration)) {
            foreach ($configuration[WorkflowConfiguration::NODE_ATTRIBUTES] as $attributeKey => $attributeConfig) {
                $key = $this->makeKey(
                    WorkflowAttributeLabelTemplate::class,
                    [
                        'workflow_name' => $workflowName,
                        'attribute_name' => $this->resolveName($attributeConfig, $attributeKey)
                    ]
                );
                yield $key => $attributeConfig['label'];
            }
        }
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
     * @param array $data
     * @return string
     * @throws \InvalidArgumentException
     */
    private function makeKey($templateClass, array $data)
    {
        return $this->keyGenerator->generate($this->makeSource($templateClass, $data));
    }

    /**
     * @param $templateClass
     * @param array $data
     * @return TranslationKeySource
     * @throws \InvalidArgumentException
     */
    private function makeSource($templateClass, array $data = [])
    {
        return new TranslationKeySource($this->getTemplate($templateClass), $data);
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
