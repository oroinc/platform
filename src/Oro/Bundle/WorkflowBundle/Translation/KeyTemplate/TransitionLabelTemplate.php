<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Transition label key template.
 */
class TransitionLabelTemplate extends TransitionTemplate
{
    public const NAME = 'transition_label';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.label';
    }
}
