<?php

namespace Oro\Bundle\WorkflowBundle\Translation;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\StepLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowLabelTemplate;

class WorkflowDefinitionTranslationFieldsIterator extends AbstractWorkflowTranslationFieldsIterator
{
    /**
     * @var WorkflowDefinition
     */
    private $workflowDefinition;

    /**
     * @var bool
     */
    private $configModified = false;

    /**
     * @param WorkflowDefinition $workflowDefinition
     */
    public function __construct(WorkflowDefinition $workflowDefinition)
    {
        $this->workflowDefinition = $workflowDefinition;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $context = new \ArrayObject([]);
        $context['workflow_name'] = $this->workflowDefinition->getName();

        yield $this->makeKey(WorkflowLabelTemplate::class, $context) => $this->workflowDefinition->getLabel();

        if ($this->hasChanges()) {
            $this->workflowDefinition->setLabel($this->pickChangedValue());
        }

        $configuration = $this->workflowDefinition->getConfiguration();

        foreach ($this->attributeFields($configuration, $context) as $key => &$attrField) {
            yield $key => $attrField;
            $this->getUpdatedValue($attrField);
        }
        unset($attrField);

        foreach ($this->transitionFields($configuration, $context) as $key => &$transitionField) {
            yield $key => $transitionField;
            $this->getUpdatedValue($transitionField);
        }
        unset($transitionField);

        foreach ($this->workflowDefinition->getSteps() as $step) {
            $context['step_name'] = $step->getName();

            yield $this->makeKey(StepLabelTemplate::class, $context) => $step->getLabel();
            if ($this->hasChanges()) {
                $this->configModified = true;
                $newValue = $this->pickChangedValue();
                $step->setLabel($newValue);
                $this->setStepField($configuration, $step->getName(), 'label', $newValue);
            }

            unset($context['step_name']);
        }

        foreach ($this->variableFields($configuration, $context) as $key => &$varField) {
            yield $key => $varField;
            $this->getUpdatedValue($varField);
        }
        unset($varField);

        if ($this->configModified) {
            $this->workflowDefinition->setConfiguration($configuration);
        }
    }

    /**
     * @param $field
     */
    private function getUpdatedValue(&$field)
    {
        if ($this->hasChanges()) {
            $this->configModified = true;
            $field = $this->pickChangedValue();
        }
    }

    /**
     * @param array $configuration
     * @param string $stepName
     * @param string $fieldName
     * @param mixed $value
     */
    private function setStepField(array &$configuration, $stepName, $fieldName, $value)
    {
        if (isset($configuration[WorkflowConfiguration::NODE_STEPS][$stepName])
            && is_array($configuration[WorkflowConfiguration::NODE_STEPS][$stepName])
        ) {
            $configuration[WorkflowConfiguration::NODE_STEPS][$stepName][$fieldName] = $value;
        }
    }
}
