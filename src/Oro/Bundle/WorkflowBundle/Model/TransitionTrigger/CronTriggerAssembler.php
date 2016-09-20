<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class CronTriggerAssembler extends TriggerAbstractAssembler
{
    /**
     * @param array $options
     * @return bool
     */
    public function canAssemble(array $options)
    {
        return !empty($options['cron']);
    }

    protected function assembleTrigger(array $options, WorkflowDefinition $workflowDefinition)
    {
        $trigger = new TransitionTriggerCron();

        return $trigger
            ->setCron($options['cron'])
            ->setFilter($this->getOption($options, 'filter', null))
            ->setQueued($this->getOption($options, 'queued', true));
    }
}
