<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Transition warning message key template.
 */
class TransitionWarningMessageTemplate extends TransitionTemplate
{
    const NAME = 'transition_warning_message';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.warning_message';
    }
}
