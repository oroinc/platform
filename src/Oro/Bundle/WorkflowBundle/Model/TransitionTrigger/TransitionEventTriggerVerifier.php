<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier\TransitionTriggerVerifierInterface;

class TransitionEventTriggerCompoundVerifier implements TransitionTriggerVerifierInterface
{
    /**
     * @var TransitionTriggerVerifierInterface[]
     */
    protected $verifiers = [];

    /**
     * @param TransitionTriggerVerifierInterface $triggerVerifier
     */
    public function addVerifier(TransitionTriggerVerifierInterface $triggerVerifier)
    {
        $this->verifiers[] = $triggerVerifier;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyTrigger(BaseTransitionTrigger $trigger)
    {
        if (!$trigger instanceof TransitionEventTrigger) {
            throw new TransitionTriggerVerifierException(
                sprintf(
                    'Trigger should be an instance of %s but %s retrieved',
                    TransitionEventTrigger::class,
                    get_class($trigger)
                )
            );
        }

        foreach ($this->verifiers as $triggerVerifier) {
            $triggerVerifier->verifyTrigger($trigger);
        }
    }
}
