<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\AssemblerException;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TriggerAssemblerInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowTransitionTriggersAssembler;

class WorkflowTransitionTriggersAssemblerTest extends \PHPUnit_Framework_TestCase
{
    public function testAssembler()
    {
        $assembler = new WorkflowTransitionTriggersAssembler();
        $someAssembler = $this->getMock(TriggerAssemblerInterface::class);
        $someAssembler->expects($this->once())->method('canAssemble')->willReturn(true);
        $trigger = $this->getMockForAbstractClass(AbstractTransitionTrigger::class);
        $someAssembler->expects($this->once())->method('assemble')->willReturn($trigger);

        $assembler->registerAssembler($someAssembler);

        $definition = (new WorkflowDefinition())
            ->setConfiguration(
                [
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        'transitionName' => [
                            WorkflowConfiguration::NODE_TRANSITION_TRIGGERS => [
                                ['cron' => '* * * * *']
                            ]
                        ]
                    ]
                ]
            );

        $triggers = $assembler->assembleTriggers($definition);

        $this->assertEquals([$trigger], $triggers);
    }

    public function testAssemblerException()
    {
        $this->setExpectedException(
            AssemblerException::class,
            sprintf(
                'Can\'t assemble trigger for %s workflow in transition %s by given options: %s',
                'workflowName',
                'transitionName',
                var_export(['cron' => '* * * * *'], 1)
            )
        );

        $assembler = new WorkflowTransitionTriggersAssembler();
        $assembler->assembleTriggers(
            (new WorkflowDefinition())
                ->setName('workflowName')
                ->setConfiguration(
                    [
                        WorkflowConfiguration::NODE_TRANSITIONS => [
                            'transitionName' => [
                                WorkflowConfiguration::NODE_TRANSITION_TRIGGERS => [
                                    ['cron' => '* * * * *']
                                ]
                            ]
                        ]
                    ]
                )
        );
    }
}
