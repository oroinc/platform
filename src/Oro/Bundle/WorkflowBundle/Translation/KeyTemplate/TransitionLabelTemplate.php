<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class TransitionLabelTemplate extends TransitionTemplate
{
    const NAME = 'transition_label';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.label';
    }
}
