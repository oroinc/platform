<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class TransitionAttributeLabelTemplate extends TransitionAttributeTemplate
{
    const NAME = 'transition_attribute_label';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.label';
    }
}
