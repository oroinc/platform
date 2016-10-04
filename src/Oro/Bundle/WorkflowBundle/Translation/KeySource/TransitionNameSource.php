<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeySource;

class TransitionNameSource extends AbstractTranslationKeySource
{
    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'oro.workflow.{{ workflow_name }}.transition.{{ transition_name }}.name';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequiredKeys()
    {
        return ['workflow_name', 'transition_name'];
    }
}
