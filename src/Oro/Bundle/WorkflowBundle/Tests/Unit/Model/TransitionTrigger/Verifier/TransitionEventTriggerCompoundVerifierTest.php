<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier\TransitionEventTriggerCompoundVerifier;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier\TransitionTriggerVerifierInterface;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Stub\TriggerStub;

class TransitionEventTriggerCompoundVerifierTest extends \PHPUnit_Framework_TestCase
{
    public function testAddVerifier()
    {
        $otherVerifier = $this->getMock(TransitionTriggerVerifierInterface::class);

        $compoundVerifier = new TransitionEventTriggerCompoundVerifier();
        $compoundVerifier->addVerifier($otherVerifier);
        $trigger = new TransitionEventTrigger();
        $otherVerifier->expects($this->once())->method('verifyTrigger')->with($trigger);

        $compoundVerifier->verifyTrigger($trigger);
    }

    public function testExceptionOnInvalidTriggerType()
    {

        $this->setExpectedException(
            TransitionTriggerVerifierException::class,
            'Trigger should be an instance of Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger but ' .
            'Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Stub\TriggerStub retrieved'
        );
        $compoundVerifier = new TransitionEventTriggerCompoundVerifier();

        $compoundVerifier->verifyTrigger(new TriggerStub());
    }
}
