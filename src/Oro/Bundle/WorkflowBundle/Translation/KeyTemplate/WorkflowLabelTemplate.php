<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Workflow label key template.
 */
class WorkflowLabelTemplate extends WorkflowTemplate
{
    const NAME = 'workflow_label';

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.label';
    }
}
