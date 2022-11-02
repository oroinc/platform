<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Transition label key template.
 */
class TransitionLabelTemplate extends TransitionTemplate
{
    const NAME = 'transition_label';

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.label';
    }
}
