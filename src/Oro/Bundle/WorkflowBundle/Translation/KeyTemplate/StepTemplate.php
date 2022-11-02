<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Step key template.
 */
class StepTemplate extends WorkflowTemplate
{
    const NAME = 'step';

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.step.{{ step_name }}';
    }

    /**
     * @return array
     */
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['step_name']);
    }
}
