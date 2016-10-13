<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\AttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionWarningMessageTemplate;
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
     * @param string $workflowName
     * @param array $configuration
     * @return array|\Generator ($translationKey => &$fieldValue)
     * @throws \InvalidArgumentException
     */
    public function &iterateConfigTranslationFields($workflowName, array &$configuration)
    {
        $data = ['workflow_name' => $workflowName];

        yield $this->makeKey(WorkflowLabelTemplate::class, $data) => $configuration['label'];

        if (array_key_exists('transitions', $configuration) && is_array($configuration['transitions'])) {
            foreach ($configuration['transitions'] as $transitionName => &$transitionConfig) {
                $data = [
                    'workflow_name' => $workflowName,
                    'transition_name' => $transitionName
                ];
                yield $this->makeKey(TransitionLabelTemplate::class, $data) => $transitionConfig['label'];
                yield $this->makeKey(TransitionWarningMessageTemplate::class, $data) => $transitionConfig['message'];
            }
            unset($transitionConfig);
        }

        if (array_key_exists('steps', $configuration) && is_array($configuration['steps'])) {
            foreach ($configuration['steps'] as $stepName => &$stepConfig) {
                $data = ['workflow_name' => $workflowName, 'step_name' => $stepName];
                yield $this->makeKey(StepLabelTemplate::class, $data) => $stepConfig['label'];
            }
            unset($stepConfig);
        }

        if (array_key_exists('attributes', $configuration) && is_array($configuration['attributes'])) {
            foreach ($configuration['attributes'] as $attributeName => &$attributeConfig) {
                $data = [
                    'workflow_name' => $workflowName,
                    'attribute_name' => $attributeName
                ];
                yield $this->makeKey(AttributeLabelTemplate::class, $data) => $attributeConfig['label'];
            }
            unset($attributeConfig);
        }
    }

    /**
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

        if (array_key_exists('transitions', $configuration) && is_array($configuration['transitions'])) {
            foreach ($configuration['transitions'] as $transitionName => $transitionConfig) {
                $data = [
                    'workflow_name' => $workflowName,
                    'transition_name' => $transitionName
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

        if (array_key_exists('attributes', $configuration) && is_array($configuration['attributes'])) {
            foreach ($configuration['attributes'] as $attributeName => $attributeConfig) {
                $key = $this->makeKey(
                    AttributeLabelTemplate::class, [
                        'workflow_name' => $workflowName,
                        'attribute_name' => $attributeName
                    ]
                );
                yield $key => $attributeConfig['label'];
            }
        }
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