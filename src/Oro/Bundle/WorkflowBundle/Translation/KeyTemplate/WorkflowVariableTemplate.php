<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Workflow variable key template.
 */
class WorkflowVariableTemplate extends WorkflowTemplate
{
    public const NAME = 'workflow_variable';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.variable.{{ variable_name }}';
    }

    /**
     * @return array
     */
    #[\Override]
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['variable_name']);
    }
}
