<?php

namespace Oro\Bundle\WorkflowBundle\Translation\KeySource;

class StepNameSource extends AbstractTranslationKeySource
{
    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'oro.workflow.{{ workflow_name }}.step.{{ step_name }}.name';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequiredKeys()
    {
        return ['workflow_name', 'step_name'];
    }
}
