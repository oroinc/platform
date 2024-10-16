<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier\TransitionEventTriggerVerifierInterface;

class TransitionEventTriggerAssembler extends AbstractTransitionTriggerAssembler
{
    /** @var TransitionEventTriggerVerifierInterface */
    private $triggerVerifier;

    public function __construct(TransitionEventTriggerVerifierInterface $triggerVerifier)
    {
        $this->triggerVerifier = $triggerVerifier;
    }

    #[\Override]
    public function canAssemble(array $options)
    {
        return !empty($options['event']);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[\Override]
    protected function verifyTrigger(BaseTransitionTrigger $trigger)
    {
        if (!$trigger instanceof TransitionEventTrigger) {
            throw new \InvalidArgumentException(
                sprintf('Expected instance of %s got %s', TransitionEventTrigger::class, get_class($trigger))
            );
        }

        $this->triggerVerifier->verifyTrigger($trigger);
    }

    /**
     * @throws \Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException
     */
    #[\Override]
    protected function assembleTrigger(array $options, WorkflowDefinition $workflowDefinition)
    {
        $trigger = new TransitionEventTrigger();

        $trigger->setEntityClass(
            !empty($options['entity_class']) ? $options['entity_class'] : $workflowDefinition->getRelatedEntity()
        );

        $trigger
            ->setEvent($options['event'])
            ->setField($this->getOption($options, 'field', null))
            ->setRelation($this->getOption($options, 'relation', null))
            ->setRequire($this->getOption($options, 'require', null));

        return $trigger;
    }
}
