<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeySource;

class WarningMessageSource extends AbstractTranslationKeySource
{
    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'oro.workflow.{{ workflow_name }}.transition.{{ transition_name }}.warning_message';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequiredKeys()
    {
        return ['workflow_name', 'transition_name'];
    }
}
