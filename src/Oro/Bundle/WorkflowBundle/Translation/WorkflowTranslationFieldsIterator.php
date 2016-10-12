<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\AttributeLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionWarningMessageTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowLabelTemplate;

class WorkflowTranslationFieldsIterator
{
    /**
     * @var array|TranslationKeyTemplateInterface[]
     */
    private $templates = [];

    /**
     * @param array $configuration
     * @return \Generator|array(TranslationKeySourceInterface $source => &$value)
     * @throws \InvalidArgumentException
     */
    public function &iterate(array &$configuration)
    {
        $workflowName = $configuration['name'];

        $data = ['workflow_name' => $workflowName];

        yield $this->makeSource(WorkflowLabelTemplate::class, $data) => $configuration['label'];

        if (array_key_exists('transitions', $configuration) && is_array($configuration['transitions'])) {
            foreach ($configuration['transitions'] as $transitionName => &$transitionConfig) {
                $data = [
                    'workflow_name' => $workflowName,
                    'transition_name' => $transitionName,
                    '_' => $transitionConfig
                ];
                yield $this->makeSource(TransitionLabelTemplate::class, $data) => $transitionConfig['label'];
                yield $this->makeSource(TransitionWarningMessageTemplate::class, $data) => $transitionConfig['message'];
            }
            unset($transitionConfig);
        }

        if (array_key_exists('steps', $configuration) && is_array($configuration['steps'])) {
            foreach ($configuration['steps'] as $stepName => &$stepConfig) {
                $data = ['workflow_name' => $workflowName, 'step_name' => $stepName, '_' => $stepConfig];
                yield $this->makeSource(StepTemplate::class, $data) => $stepConfig['label'];
            }
            unset($stepConfig);
        }

        if (array_key_exists('attributes', $configuration) && is_array($configuration['attributes'])) {
            foreach ($configuration['attributes'] as $attributeName => &$attributeConfig) {
                $data = ['workflow_name' => $workflowName, 'attribute_name' => $attributeName, '_' => $attributeConfig];
                yield $this->makeSource(AttributeLabelTemplate::class, $data) => $attributeConfig['label'];
            }
            unset($attributeConfig);
        }
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
        if (array_key_exists($templateClass, $this->templates)) {
            return $this->templates[$templateClass];
        }

        if (!is_a($templateClass, TranslationKeyTemplateInterface::class, true)) {
            throw new \InvalidArgumentException(
                sprintf('Template class must implement %s', TranslationKeyTemplateInterface::class)
            );
        }

        return $this->templates[$templateClass] = new $templateClass;
    }
}