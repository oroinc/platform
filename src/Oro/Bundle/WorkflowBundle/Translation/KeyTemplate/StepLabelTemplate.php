<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class StepLabelTemplate extends StepTemplate
{
    const NAME = 'step_label';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.label';
    }
}
