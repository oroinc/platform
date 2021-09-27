<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Workflow attribute label key template.
 */
class WorkflowAttributeLabelTemplate extends WorkflowAttributeTemplate
{
    const NAME = 'workflow_attribute_label';

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.label';
    }
}
