<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowLabelTemplate;

class WorkflowConfigurationTranslationFieldsIterator extends AbstractWorkflowTranslationFieldsIterator
{
    /** @var string */
    private $workflowName;

    /** @var array */
    private $configuration;

    /**
     * @param string $workflowName
     * @param array $configuration
     */
    public function __construct($workflowName, array $configuration)
    {
        $this->workflowName = $workflowName;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $context = new \ArrayObject([]);
        $context['workflow_name'] = $this->workflowName;

        yield $this->makeKey(WorkflowLabelTemplate::class, $context) => $this->getOrNull($this->configuration, 'label');

        if ($this->hasChanges()) {
            $this->configuration['label'] = $this->pickChangedValue();
        }

        foreach ($this->attributeFields($this->configuration, $context) as $key => &$attributeFieldValue) {
            yield $key => $attributeFieldValue;
            if ($this->hasChanges()) {
                $attributeFieldValue = $this->pickChangedValue();
            }
        }
        unset($attributeFieldValue);

        foreach ($this->transitionFields($this->configuration, $context) as $key => &$transitionFieldValue) {
            yield $key => $transitionFieldValue;
            if ($this->hasChanges()) {
                $transitionFieldValue = $this->pickChangedValue();
            }
        }
        unset($transitionFieldValue);

        foreach ($this->stepFields($this->configuration, $context) as $key => &$stepFieldValue) {
            yield $key => $stepFieldValue;
            if ($this->hasChanges()) {
                $stepFieldValue = $this->pickChangedValue();
            }
        }
        unset($stepFieldValue);

        foreach ($this->variableFields($this->configuration, $context) as $key => &$varField) {
            yield $key => $varField;
            if ($this->hasChanges()) {
                $varField = $this->pickChangedValue();
            }
        }
        unset($varField);
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param array $array
     * @param string|integer $option
     * @return mixed|null
     */
    private function getOrNull(array $array, $option)
    {
        return array_key_exists($option, $array) ? $array[$option] : null;
    }
}
