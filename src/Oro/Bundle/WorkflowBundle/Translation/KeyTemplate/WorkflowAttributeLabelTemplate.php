<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class WorkflowAttributeLabelTemplate extends WorkflowAttributeTemplate
{
    const NAME = 'workflow_attribute_label';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.label';
    }
}
