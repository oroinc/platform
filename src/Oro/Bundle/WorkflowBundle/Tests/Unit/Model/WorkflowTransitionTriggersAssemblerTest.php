<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowTransitionTriggersAssembler;

class WorkflowTransitionTriggersAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $expected
     * @param WorkflowDefinition $workflowDefinition
     * @dataProvider assembleData
     */
    public function testAssembleTriggers(array $expected, WorkflowDefinition $workflowDefinition)
    {
        $triggers = (new WorkflowTransitionTriggersAssembler())->assembleTriggers($workflowDefinition);

        $c = count($triggers);
        for ($i = 0; $c !== $i; $i++) {
            $this->assertEquals($expected[$i], $triggers[$i]);
        }
    }

    /**
     * @return array
     */
    public function assembleData()
    {
        $configuration = [
            'simple' => [
                [
                    (new TransitionTriggerCron())->setCron('* * * * *'),
                    (new TransitionTriggerEvent())->setEvent('update')
                ],
                [
                    ['cron' => '* * * * *'],
                    ['event' => 'update']
                ]
            ],
            'full' => [
                [
                    (new TransitionTriggerCron())
                        ->setCron('* * * * *')
                        ->setFilter('filter != true')
                        ->setQueued(false),
                    (new TransitionTriggerEvent())
                        ->setEvent('update')
                        ->setField('field')
                        ->setQueued(false)
                        ->setEntityClass('EntityClass')
                        ->setRelation('relation.here')
                        ->setRequire('expression()')
                ],
                [
                    [
                        'cron' => '* * * * *',
                        'filter' => 'filter != true',
                        'queued' => false
                    ],
                    [
                        'event' => 'update',
                        'field' => 'field',
                        'queued' => false,
                        'entity_class' => 'EntityClass',
                        'relation' => 'relation.here',
                        'require' => 'expression()'
                    ]
                ]
            ]
        ];

        $data = [];
        foreach ($configuration as $transition => $case) {
            list($expected, $config) = $case;

            $definition = new WorkflowDefinition();

            foreach ($expected as $trigger) {
                /**@var AbstractTransitionTrigger $trigger */
                $trigger->setTransitionName($transition);
                $trigger->setWorkflowDefinition($definition);
            }

            $data[$transition] = [
                $expected,
                $definition->setConfiguration(
                    [
                        WorkflowConfiguration::NODE_TRANSITIONS => [
                            $transition => [
                                WorkflowConfiguration::NODE_TRANSITION_TRIGGERS => $config
                            ]
                        ]
                    ]
                )
            ];
        }

        return $data;
    }
}
