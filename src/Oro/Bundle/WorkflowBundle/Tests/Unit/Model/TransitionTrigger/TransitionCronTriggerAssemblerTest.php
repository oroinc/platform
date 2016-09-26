<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionCronTriggerAssembler;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggerCronVerifier;

class TransitionCronTriggerAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TransitionTriggerCronVerifier|\PHPUnit_Framework_MockObject_MockObject */
    protected $verifier;

    /** @var TransitionCronTriggerAssembler */
    protected $assembler;

    protected function setUp()
    {
        $this->verifier = $this->getMockBuilder(TransitionTriggerCronVerifier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assembler = new TransitionCronTriggerAssembler($this->verifier);
    }

    /**
     * @dataProvider canAssembleData
     * @param bool $expected
     * @param array $options
     */
    public function testCanAssemble($expected, array $options)
    {
        $this->assertEquals($expected, $this->assembler->canAssemble($options));
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
        $cronOpt = '* * * * *';
        $filterOpt = 'a = b';
        $queuedOpt = false;
        $transitionOpt = 'transitionName';
        $workflowDefinitionOpt = new WorkflowDefinition();

        $this->verifier->expects($this->once())
            ->method('verify')
            ->with($this->isInstanceOf(TransitionCronTrigger::class));

        /** @var TransitionCronTrigger $trigger */
        $trigger = $this->assembler->assemble(
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
        $cronOpt = '* * * * *';
        $filterOpt = null;
        $queuedOpt = true;
        $transitionOpt = 'transitionName';
        $workflowDefinitionOpt = new WorkflowDefinition();

        $this->verifier->expects($this->once())
            ->method('verify')
            ->with($this->isInstanceOf(TransitionCronTrigger::class));

        /** @var TransitionCronTrigger $trigger */
        $trigger = $this->assembler->assemble(
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
