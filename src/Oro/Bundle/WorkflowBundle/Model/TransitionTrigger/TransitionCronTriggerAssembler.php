<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TransitionCronTriggerAssembler extends AbstractTransitionTriggerAssembler
{
    /** @var TransitionTriggerCronVerifier */
    protected $triggerCronVerifier;

    /**
     * @param TransitionTriggerCronVerifier $triggerCronVerifier
     */
    public function __construct(TransitionTriggerCronVerifier $triggerCronVerifier)
    {
        $this->triggerCronVerifier = $triggerCronVerifier;
    }

    /**
     * {@inheritdoc}
     */
    public function canAssemble(array $options)
    {
        return !empty($options['cron']);
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    protected function verifyTrigger(BaseTransitionTrigger $trigger)
    {
        if (!$trigger instanceof TransitionCronTrigger) {
            throw new \InvalidArgumentException(
                sprintf('Expected instance of %s got %s', TransitionCronTrigger::class, get_class($trigger))
            );
        }

        $this->triggerCronVerifier->verify($trigger);
    }

    /**
     * {@inheritdoc}
     */
    protected function assembleTrigger(array $options, WorkflowDefinition $workflowDefinition)
    {
        $trigger = new TransitionCronTrigger();
        $trigger
            ->setWorkflowDefinition($workflowDefinition)
            ->setCron($options['cron'])
            ->setFilter($this->getOption($options, 'filter', null))
            ->setQueued($this->getOption($options, 'queued', true));

        return $trigger;
    }
}
