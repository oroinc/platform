<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Workflow variable form option key template.
 */
class WorkflowVariableFormOptionTemplate extends WorkflowVariableTemplate
{
    const NAME = 'workflow_variable_form_option';

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.{{ option_name }}';
    }

    /**
     * @return array
     */
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['option_name']);
    }
}
