<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;

interface TransitionEventTriggerVerifierInterface
{
    /**
     * @param TransitionEventTrigger $trigger
     * @throws TransitionTriggerVerifierException
     */
    public function verifyTrigger(TransitionEventTrigger $trigger);
}
