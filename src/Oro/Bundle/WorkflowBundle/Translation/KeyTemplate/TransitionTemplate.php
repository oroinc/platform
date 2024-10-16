<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Transition key template.
 */
class TransitionTemplate extends WorkflowTemplate
{
    const NAME = 'transition';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.transition.{{ transition_name }}';
    }

    /**
     * @return array
     */
    #[\Override]
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['transition_name']);
    }
}
