<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Transition button label key template.
 */
class TransitionButtonLabelTemplate extends TransitionTemplate
{
    public const NAME = 'transition_button_label';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.button_label';
    }
}
