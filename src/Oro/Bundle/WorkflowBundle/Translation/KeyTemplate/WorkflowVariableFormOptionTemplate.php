<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class WorkflowVariableFormOptionTemplate extends WorkflowVariableTemplate
{
    const NAME = 'workflow_variable_form_option';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.{{ option_name }}.label';
    }
}
