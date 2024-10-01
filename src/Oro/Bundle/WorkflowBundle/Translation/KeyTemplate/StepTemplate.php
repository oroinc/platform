<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Step key template.
 */
class StepTemplate extends WorkflowTemplate
{
    const NAME = 'step';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.step.{{ step_name }}';
    }

    /**
     * @return array
     */
    #[\Override]
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['step_name']);
    }
}
