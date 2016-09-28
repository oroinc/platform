<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;

interface TransitionTriggerVerifierInterface
{
    /**
     * @param BaseTransitionTrigger $trigger
     * @return void
     * @throws TransitionTriggerVerifierException
     */
    public function verifyTrigger(BaseTransitionTrigger $trigger);
}
