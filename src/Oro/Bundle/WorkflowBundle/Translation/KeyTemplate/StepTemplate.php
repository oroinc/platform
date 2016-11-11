<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class StepTemplate extends WorkflowTemplate
{
    const NAME = 'step';

    /**
     * @return string
     */
    public function getTemplate()
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
