<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionCronTriggerAssembler;

class TransitionCronTriggerAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider canAssembleData
     * @param bool $expected
     * @param array $options
     */
    public function testCanAssemble($expected, array $options)
    {
        $cronTriggerAssembler = new TransitionCronTriggerAssembler();

        $this->assertEquals($expected, $cronTriggerAssembler->canAssemble($options));
    }

    /**
     * @return array
     */
    public function canAssembleData()
    {
        return [
            'can' => [
                true,
                [
                    'cron' => '* * * * *'
                ]
            ],
            'can not. cron null' => [
                false,
                [
                    'cron' => null
                ]
            ],
            'can not: cron not defined' => [
                false,
                [
                    'event' => 'create'
                ]
            ]
        ];
    }

    public function testAssemble()
    {
        $cronTriggerAssembler = new TransitionCronTriggerAssembler();

        $cronOpt = '* * * * *';
        $filterOpt = 'a = b';
        $queuedOpt = false;
        $transitionOpt = 'transitionName';
        $workflowDefinitionOpt = new WorkflowDefinition();

        /**
         * @var TransitionCronTrigger $trigger
         */
        $trigger = $cronTriggerAssembler->assemble(
            [
                'cron' => $cronOpt,
                'filter' => $filterOpt,
                'queued' => $queuedOpt
            ],
            $transitionOpt,
            $workflowDefinitionOpt
        );

        $this->assertInstanceOf(
            TransitionCronTrigger::class,
            $trigger,
            'Must return new instance of cron trigger entity'
        );

        $this->assertSame($cronOpt, $trigger->getCron());
        $this->assertSame($filterOpt, $trigger->getFilter());
        $this->assertSame($queuedOpt, $trigger->isQueued());
        $this->assertSame($transitionOpt, $trigger->getTransitionName());
        $this->assertSame($workflowDefinitionOpt, $trigger->getWorkflowDefinition());
    }

    public function testAssembleDefaults()
    {
        $cronTriggerAssembler = new TransitionCronTriggerAssembler();

        $cronOpt = '* * * * *';
        $filterOpt = null;
        $queuedOpt = true;
        $transitionOpt = 'transitionName';
        $workflowDefinitionOpt = new WorkflowDefinition();

        /**
         * @var TransitionCronTrigger $trigger
         */
        $trigger = $cronTriggerAssembler->assemble(
            [
                'cron' => $cronOpt,
            ],
            $transitionOpt,
            $workflowDefinitionOpt
        );

        $this->assertInstanceOf(
            TransitionCronTrigger::class,
            $trigger,
            'Must return new instance of cron trigger entity'
        );

        $this->assertSame($cronOpt, $trigger->getCron());
        $this->assertSame($filterOpt, $trigger->getFilter());
        $this->assertSame($queuedOpt, $trigger->isQueued());
        $this->assertSame($transitionOpt, $trigger->getTransitionName());
        $this->assertSame($workflowDefinitionOpt, $trigger->getWorkflowDefinition());
    }
}
