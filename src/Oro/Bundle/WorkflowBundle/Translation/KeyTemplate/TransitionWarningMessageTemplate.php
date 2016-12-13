<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeyTemplate;

class TransitionWarningMessageTemplate extends TransitionTemplate
{
    const NAME = 'transition_warning_message';

    /**
     * @return string
     */
    public function getTemplate()
    {
        return parent::getTemplate() . '.warning_message';
    }
}
