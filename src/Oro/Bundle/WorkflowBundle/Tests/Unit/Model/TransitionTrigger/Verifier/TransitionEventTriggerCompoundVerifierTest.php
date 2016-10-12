<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier\TransitionEventTriggerCompoundVerifier;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier\TransitionEventTriggerVerifierInterface;

class TransitionEventTriggerCompoundVerifierTest extends \PHPUnit_Framework_TestCase
{
    public function testAddVerifier()
    {
        $trigger = new TransitionEventTrigger();

        $otherVerifier = $this->getMock(TransitionEventTriggerVerifierInterface::class);
        $otherVerifier->expects($this->once())->method('verifyTrigger')->with($trigger);

        $compoundVerifier = new TransitionEventTriggerCompoundVerifier();
        $compoundVerifier->addVerifier($otherVerifier);

        $compoundVerifier->verifyTrigger($trigger);
    }
}
