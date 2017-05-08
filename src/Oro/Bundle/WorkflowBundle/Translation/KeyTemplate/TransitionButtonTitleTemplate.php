<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class TransitionButtonTitleTemplate extends TransitionTemplate
{
    const NAME = 'transition_button_title';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.button_title';
    }
}
