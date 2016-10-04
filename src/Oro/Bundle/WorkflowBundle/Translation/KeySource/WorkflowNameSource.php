<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeySource;

class WorkflowNameSource extends AbstractTranslationKeySource
{
    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'oro.workflow.{{ workflow_name }}.name';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequiredKeys()
    {
        return ['workflow_name'];
    }
}
