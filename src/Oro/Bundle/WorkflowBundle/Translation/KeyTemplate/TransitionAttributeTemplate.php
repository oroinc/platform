<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

/**
 * Transition attribute key template.
 */
class TransitionAttributeTemplate extends TransitionTemplate
{
    public const NAME = 'transition_attribute';

    #[\Override]
    public function getTemplate(): string
    {
        return parent::getTemplate() . '.attribute.{{ attribute_name }}';
    }

    /**
     * @return array
     */
    #[\Override]
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['attribute_name']);
    }
}
