<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class WorkflowVariableLabelTemplate extends WorkflowVariableTemplate
{
    const NAME = 'workflow_variable_label';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.label';
    }
}
