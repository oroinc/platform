<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;

class TransitionEventTriggerCompoundVerifier implements TransitionEventTriggerVerifierInterface
{
    /** @var array|TransitionEventTriggerVerifierInterface[] */
    protected $verifiers = [];

    /**
     * @param TransitionEventTriggerVerifierInterface $triggerVerifier
     */
    public function addVerifier(TransitionEventTriggerVerifierInterface $triggerVerifier)
    {
        $this->verifiers[] = $triggerVerifier;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyTrigger(TransitionEventTrigger $trigger)
    {
        foreach ($this->verifiers as $triggerVerifier) {
            $triggerVerifier->verifyTrigger($trigger);
        }
    }
}
