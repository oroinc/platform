<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowLabelTemplate;

class WorkflowConfigurationTranslationFieldsIterator extends AbstractWorkflowTranslationFieldsIterator
{
    /** @var array */
    private $configuration;

    /** @var string */
    private $workflowName;

    /**
     * @param string $workflowName
     * @param array $configuration
     */
    public function __construct($workflowName, array $configuration)
    {
        $this->configuration = $configuration;
        $this->workflowName = $workflowName;
    }

    public function getIterator()
    {
        $context = new \ArrayObject([]);

        $context['workflow_name'] = $this->workflowName;

        yield $this->makeKey(WorkflowLabelTemplate::class, $context) => $this->configuration['label'];

        if ($this->hasChanges()) {
            $this->configuration['label'] = $this->pickChangedValue();
        }

        foreach ($this->attributeFields($configuration, $context) as $key => &$attributeFieldValue) {
            yield $key => $attributeFieldValue;
            if ($this->hasChanges()) {
                $attributeFieldValue = $this->pickChangedValue();
            }
        }
        unset($attributeFieldValue);

        foreach ($this->transitionFields($configuration, $context) as $key => &$transitionFieldValue) {
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
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
}
