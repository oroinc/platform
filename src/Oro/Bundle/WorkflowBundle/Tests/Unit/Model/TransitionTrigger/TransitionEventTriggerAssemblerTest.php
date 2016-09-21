<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionEventTriggerAssembler;

class TransitionEventTriggerAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider canAssembleData
     * @param bool $expected
     * @param array $options
     */
    public function testCanAssemble($expected, array $options)
    {
        $cronTriggerAssembler = new TransitionEventTriggerAssembler();

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
                    'event' => 'create'
                ]
            ],
            'can not. cron null' => [
                false,
                [
                    'event' => null
                ]
            ],
            'can not: cron not defined' => [
                false,
                [
                    'cron' => '* * * * *'
                ]
            ]
        ];
    }

    public function testAssemble()
    {
        $cronTriggerAssembler = new TransitionEventTriggerAssembler();

        $eventOpt = 'update';
        $entityClassOpt = '\EntityClass';
        $fieldOpt = 'field';
        $relationOpt = 'relation';
        $requireOpt = 'expr()';

        $queuedOpt = false;
        $transitionOpt = 'transitionName';
        $workflowDefinitionOpt = new WorkflowDefinition();

        /**
         * @var TransitionEventTrigger $trigger
         */
        $trigger = $cronTriggerAssembler->assemble(
            [
                'event' => $eventOpt,
                'entity_class' => $entityClassOpt,
                'field' => $fieldOpt,
                'relation' => $relationOpt,
                'require' => $requireOpt,
                'queued' => $queuedOpt
            ],
            $transitionOpt,
            $workflowDefinitionOpt
        );

        $this->assertInstanceOf(
            TransitionEventTrigger::class,
            $trigger,
            'Must return new instance of event trigger entity'
        );

        $this->assertSame($eventOpt, $trigger->getEvent());
        $this->assertSame($entityClassOpt, $trigger->getEntityClass());
        $this->assertSame($fieldOpt, $trigger->getField());
        $this->assertSame($relationOpt, $trigger->getRelation());
        $this->assertSame($requireOpt, $trigger->getRequire());

        $this->assertSame($queuedOpt, $trigger->isQueued());
        $this->assertSame($transitionOpt, $trigger->getTransitionName());
        $this->assertSame($workflowDefinitionOpt, $trigger->getWorkflowDefinition());
    }

    public function testAssembleDefaults()
    {
        $cronTriggerAssembler = new TransitionEventTriggerAssembler();

        $eventOpt = 'create';
        $entityClassOpt = '\WorkflowRelatedEntity';
        $fieldOpt = null;
        $relationOpt = null;
        $requireOpt = null;

        $queuedOpt = true;
        $transitionOpt = 'transitionName';
        $workflowDefinitionOpt = new WorkflowDefinition();
        $workflowDefinitionOpt->setRelatedEntity($entityClassOpt);

        /**
         * @var TransitionEventTrigger $trigger
         */
        $trigger = $cronTriggerAssembler->assemble(
            [
                'event' => $eventOpt,
            ],
            $transitionOpt,
            $workflowDefinitionOpt
        );

        $this->assertInstanceOf(
            TransitionEventTrigger::class,
            $trigger,
            'Must return new instance of event trigger entity'
        );

        $this->assertSame($eventOpt, $trigger->getEvent());
        $this->assertSame($entityClassOpt, $trigger->getEntityClass());
        $this->assertSame($fieldOpt, $trigger->getField());
        $this->assertSame($relationOpt, $trigger->getRelation());
        $this->assertSame($requireOpt, $trigger->getRequire());

        $this->assertSame($queuedOpt, $trigger->isQueued());
        $this->assertSame($transitionOpt, $trigger->getTransitionName());
        $this->assertSame($workflowDefinitionOpt, $trigger->getWorkflowDefinition());
    }
}
