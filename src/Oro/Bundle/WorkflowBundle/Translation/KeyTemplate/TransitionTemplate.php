<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class TransitionTemplate extends WorkflowTemplate
{
    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.transition.{{ transition_name }}';
    }

    /**
     * @return array
     */
    public function getRequiredKeys()
    {
        return array_merge(parent::getRequiredKeys(), ['transition_name']);
    }
}
