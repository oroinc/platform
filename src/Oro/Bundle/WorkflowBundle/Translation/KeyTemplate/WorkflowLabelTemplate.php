<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Workflow label key template.
 */
class WorkflowLabelTemplate extends WorkflowTemplate
{
    public const NAME = 'workflow_label';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.label';
    }
}
