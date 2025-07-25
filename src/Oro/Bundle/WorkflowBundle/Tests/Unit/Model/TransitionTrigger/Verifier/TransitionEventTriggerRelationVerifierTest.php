<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier\TransitionEventTriggerRelationVerifier;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;
use PHPUnit\Framework\TestCase;

class TransitionEventTriggerRelationVerifierTest extends TestCase
{
    private TransitionEventTriggerRelationVerifier $verifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->verifier = new TransitionEventTriggerRelationVerifier();
    }

    public function testRelationExpectedException(): void
    {
        $definition = (new WorkflowDefinition())->setName('test_workflow');
        $definition->setRelatedEntity(EntityStub::class);

        $trigger = new TransitionEventTrigger();
        $trigger->setWorkflowDefinition($definition);
        $trigger->setEntityClass(\stdClass::class);
        $trigger->setTransitionName('test_transition');
        $trigger->setEvent('update');

        $this->expectException(TransitionTriggerVerifierException::class);
        $this->expectExceptionMessage(
            'Relation option is mandatory for non workflow related entity based event triggers. ' .
            'Empty relation property met in `test_workflow` workflow for `test_transition` transition ' .
            'with entity `stdClass` by event `update`'
        );

        $this->verifier->verifyTrigger($trigger);
    }

    public function testRelationOk(): void
    {
        $definition = new WorkflowDefinition();
        $definition->setRelatedEntity(EntityStub::class);

        $trigger = new TransitionEventTrigger();
        $trigger->setWorkflowDefinition($definition);
        $trigger->setEntityClass(\stdClass::class);
        $trigger->setRelation('entity');

        $this->verifier->verifyTrigger($trigger);
    }

    public function testRelationIsNotNeeded(): void
    {
        $definition = new WorkflowDefinition();
        $definition->setRelatedEntity(EntityStub::class);

        $trigger = new TransitionEventTrigger();
        $trigger->setWorkflowDefinition($definition);
        $trigger->setEntityClass(EntityStub::class);

        $this->verifier->verifyTrigger($trigger);
    }
}
