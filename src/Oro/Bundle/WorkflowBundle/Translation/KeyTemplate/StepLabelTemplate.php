<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class StepLabelTemplate extends StepTemplate
{
    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.label';
    }
}
