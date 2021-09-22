<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Workflow variable key template.
 */
class WorkflowVariableTemplate extends WorkflowTemplate
{
    const NAME = 'workflow_variable';

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.variable.{{ variable_name }}';
    }

    /**
     * @return array
     */
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['variable_name']);
    }
}
