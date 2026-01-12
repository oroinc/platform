<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;

/**
 * Defines the contract for verifying transition event trigger configurations.
 *
 * Implementations validate transition event triggers to ensure they are properly configured
 * and compatible with the workflow definition and entity relationships.
 */
interface TransitionEventTriggerVerifierInterface
{
    /**
     * @throws TransitionTriggerVerifierException
     */
    public function verifyTrigger(TransitionEventTrigger $trigger);
}
