<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\CronTriggerAssembler;

class CronTriggerAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider canAssembleData
     * @param bool $expected
     * @param array $options
     */
    public function testCanAssemble($expected, array $options)
    {
        $cronTriggerAssembler = new CronTriggerAssembler();

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
        $cronTriggerAssembler = new CronTriggerAssembler();

        $cronOpt = '* * * * *';
        $filterOpt = 'a = b';
        $queuedOpt = false;
        $transitionOpt = 'transitionName';
        $workflowDefinitionOpt = new WorkflowDefinition();

        /**
         * @var TransitionTriggerCron $trigger
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
            TransitionTriggerCron::class,
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
        $cronTriggerAssembler = new CronTriggerAssembler();

        $cronOpt = '* * * * *';
        $filterOpt = null;
        $queuedOpt = true;
        $transitionOpt = 'transitionName';
        $workflowDefinitionOpt = new WorkflowDefinition();

        /**
         * @var TransitionTriggerCron $trigger
         */
        $trigger = $cronTriggerAssembler->assemble(
            [
                'cron' => $cronOpt,
            ],
            $transitionOpt,
            $workflowDefinitionOpt
        );

        $this->assertInstanceOf(
            TransitionTriggerCron::class,
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
