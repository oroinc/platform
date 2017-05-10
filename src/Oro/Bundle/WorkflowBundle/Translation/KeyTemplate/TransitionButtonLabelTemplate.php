<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class TransitionButtonLabelTemplate extends TransitionTemplate
{
    const NAME = 'transition_button_label';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.button_label';
    }
}
