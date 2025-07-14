<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier\TransitionEventTriggerCompoundVerifier;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier\TransitionEventTriggerVerifierInterface;
use PHPUnit\Framework\TestCase;

class TransitionEventTriggerCompoundVerifierTest extends TestCase
{
    public function testAddVerifier(): void
    {
        $trigger = new TransitionEventTrigger();

        $otherVerifier = $this->createMock(TransitionEventTriggerVerifierInterface::class);
        $otherVerifier->expects($this->once())
            ->method('verifyTrigger')
            ->with($trigger);

        $compoundVerifier = new TransitionEventTriggerCompoundVerifier();
        $compoundVerifier->addVerifier($otherVerifier);

        $compoundVerifier->verifyTrigger($trigger);
    }
}
