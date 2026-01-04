<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Workflow attribute label key template.
 */
class WorkflowAttributeLabelTemplate extends WorkflowAttributeTemplate
{
    public const NAME = 'workflow_attribute_label';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.label';
    }
}
