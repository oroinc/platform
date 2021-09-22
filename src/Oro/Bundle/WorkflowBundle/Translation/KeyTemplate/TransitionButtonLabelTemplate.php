<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Transition button label key template.
 */
class TransitionButtonLabelTemplate extends TransitionTemplate
{
    const NAME = 'transition_button_label';

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.button_label';
    }
}
