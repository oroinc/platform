<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class TransitionLabelTemplate extends TransitionTemplate
{
    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.label';
    }
}
