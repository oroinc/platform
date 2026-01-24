<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * Assembles transition cron triggers from configuration options.
 *
 * This assembler creates {@see TransitionCronTrigger} instances from configuration arrays containing
 * cron expressions. It verifies the trigger configuration using a cron verifier and supports
 * optional filter expressions and queued execution settings.
 */
class TransitionCronTriggerAssembler extends AbstractTransitionTriggerAssembler
{
    /** @var TransitionTriggerCronVerifier */
    protected $triggerCronVerifier;

    public function __construct(TransitionTriggerCronVerifier $triggerCronVerifier)
    {
        $this->triggerCronVerifier = $triggerCronVerifier;
    }

    #[\Override]
    public function canAssemble(array $options)
    {
        return !empty($options['cron']);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[\Override]
    protected function verifyTrigger(BaseTransitionTrigger $trigger)
    {
        if (!$trigger instanceof TransitionCronTrigger) {
            throw new \InvalidArgumentException(
                sprintf('Expected instance of %s got %s', TransitionCronTrigger::class, get_class($trigger))
            );
        }

        $this->triggerCronVerifier->verify($trigger);
    }

    #[\Override]
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
