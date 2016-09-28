<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier\TransitionTriggerVerifierInterface;

class TransitionEventTriggerAssembler extends AbstractTransitionTriggerAssembler
{
    /** @var TransitionTriggerVerifierInterface */
    private $triggerVerifier;

    /**
     * @param TransitionTriggerVerifierInterface $triggerVerifier
     */
    public function __construct(TransitionTriggerVerifierInterface $triggerVerifier)
    {
        $this->triggerVerifier = $triggerVerifier;
    }

    /**
     * {@inheritdoc}
     */
    public function canAssemble(array $options)
    {
        return !empty($options['event']);
    }

    /**
     * {@inheritdoc}
     * @throws \Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException
     */
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

        $this->triggerVerifier->verifyTrigger($trigger);

        return $trigger;
    }
}
