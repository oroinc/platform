<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;

/**
 * Compound verifier that delegates transition event trigger verification to multiple verifiers.
 *
 * This verifier aggregates multiple trigger verifiers and executes all of them in sequence,
 * allowing for comprehensive validation of transition event triggers.
 */
class TransitionEventTriggerCompoundVerifier implements TransitionEventTriggerVerifierInterface
{
    /** @var array|TransitionEventTriggerVerifierInterface[] */
    protected $verifiers = [];

    public function addVerifier(TransitionEventTriggerVerifierInterface $triggerVerifier)
    {
        $this->verifiers[] = $triggerVerifier;
    }

    #[\Override]
    public function verifyTrigger(TransitionEventTrigger $trigger)
    {
        foreach ($this->verifiers as $triggerVerifier) {
            $triggerVerifier->verifyTrigger($trigger);
        }
    }
}
