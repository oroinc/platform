<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class TransitionNameTemplate extends TransitionTemplate
{
    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.name';
    }
}
