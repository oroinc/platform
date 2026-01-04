<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Workflow variable form option key template.
 */
class WorkflowVariableFormOptionTemplate extends WorkflowVariableTemplate
{
    public const NAME = 'workflow_variable_form_option';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.{{ option_name }}';
    }

    /**
     * @return array
     */
    #[\Override]
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['option_name']);
    }
}
