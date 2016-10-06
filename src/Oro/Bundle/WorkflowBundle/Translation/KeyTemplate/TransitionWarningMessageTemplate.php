<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class TransitionWarningMessageTemplate extends TransitionTemplate
{
    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.warning_message';
    }
}
