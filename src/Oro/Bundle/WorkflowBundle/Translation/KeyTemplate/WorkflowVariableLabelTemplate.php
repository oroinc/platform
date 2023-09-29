<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Workflow variable label key template.
 */
class WorkflowVariableLabelTemplate extends WorkflowVariableTemplate
{
    const NAME = 'workflow_variable_label';

    public function getTemplate(): string
    {
        return parent::getTemplate() . '.label';
    }
}
