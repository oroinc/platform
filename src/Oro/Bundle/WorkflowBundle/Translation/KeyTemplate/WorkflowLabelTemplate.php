<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class WorkflowLabelTemplate extends WorkflowTemplate
{
    const NAME = 'workflow_label';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.label';
    }
}
