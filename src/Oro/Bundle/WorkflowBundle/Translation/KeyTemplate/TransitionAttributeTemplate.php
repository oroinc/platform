<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class TransitionAttributeTemplate extends TransitionTemplate
{
    const NAME = 'transition_attribute';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.attribute.{{ attribute_name }}';
    }

    /**
     * @return array
     */
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['attribute_name']);
    }
}
