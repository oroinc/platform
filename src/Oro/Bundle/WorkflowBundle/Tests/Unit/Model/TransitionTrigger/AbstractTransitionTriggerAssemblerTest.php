<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Stub\AbstractTransitionTriggerAssemblerStub;

class AbstractTransitionTriggerAssemblerTest extends \PHPUnit\Framework\TestCase
{
    public function testCommonSetters()
    {
        $assembler = new AbstractTransitionTriggerAssemblerStub(true);

        $workflowDefinition = new WorkflowDefinition();

        $trigger = $assembler->assemble(
            [
                'queued' => false
            ],
            'transition',
            $workflowDefinition
        );

        $this->assertFalse($trigger->isQueued());
        $this->assertSame('transition', $trigger->getTransitionName());
        $this->assertSame($workflowDefinition, $trigger->getWorkflowDefinition());
    }

    public function testCommonSettersDefaults()
    {
        $assembler = new AbstractTransitionTriggerAssemblerStub(true);

        $workflowDefinition = new WorkflowDefinition();

        $trigger = $assembler->assemble(
            [],
            'transition',
            $workflowDefinition
        );

        $this->assertTrue($trigger->isQueued());
        $this->assertSame('transition', $trigger->getTransitionName());
        $this->assertSame($workflowDefinition, $trigger->getWorkflowDefinition());
    }

    public function testInvalidOptionsException()
    {
        $assembler = new AbstractTransitionTriggerAssemblerStub(false);

        $workflowDefinition = (new WorkflowDefinition())->setName('workflowName');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Can not assemble trigger for transition %s in workflow %s by provided options %s.',
                'transitionName',
                'workflowName',
                var_export(['optKey' => 'optVal'], 1)
            )
        );

        $assembler->assemble(
            ['optKey' => 'optVal'],
            'transitionName',
            $workflowDefinition
        );
    }
}
