<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class WorkflowLabelTemplate extends WorkflowTemplate
{
    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.label';
    }
}
