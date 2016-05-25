<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Generator;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Generator\ProcessConfigurationGenerator;
use Oro\Bundle\WorkflowBundle\Generator\TriggerScheduleOptionsVerifier;

class ProcessConfigurationGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TriggerScheduleOptionsVerifier|\PHPUnit_Framework_MockObject_MockObject */
    protected $verifier;

    /** @var string */
    protected $workflowItemEntityClass = 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem';

    /** @var ProcessConfigurationGenerator */
    protected $generator;

    /** @var WorkflowDefinition|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowDefinition;

    protected function setUp()
    {
        $this->verifier = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Generator\TriggerScheduleOptionsVerifier')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->generator = new ProcessConfigurationGenerator($this->verifier, $this->workflowItemEntityClass);
    }

    /**
     * @dataProvider dataGenerateForScheduledTransition
     * @param array $transitionConfigurations
     * @param array $expects
     */
    public function testGenerateForScheduledTransition(array $transitionConfigurations, array $expects)
    {
        $this->workflowDefinition->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([WorkflowConfiguration::NODE_TRANSITIONS => $transitionConfigurations]);
        $this->workflowDefinition->expects($this->once())
            ->method('getName')
            ->willReturn('workflow_name');

        $this->verifier->expects(
            $this->exactly(\count(\array_filter($transitionConfigurations, function ($v) {
                return array_key_exists('schedule', $v);
            })))
        )->method('verify');

        $generated = $this->generator->generateForScheduledTransition($this->workflowDefinition);

        $this->assertEquals($expects, $generated);
    }

    /**
     * @return array
     */
    public function dataGenerateForScheduledTransition()
    {
        return [
            'gen_all_cases' => [
                [
                    'transitionOne' => [
                        'name' => 'transition_one',
                        'schedule' => ['cron' => '42 * * * *']
                    ],
                    'transitionTwo' => [
                        'name' => 'transition_wont_be_managed'
                    ],
                    'transitionThree' => [
                        'name' => 'transition_three',
                        'schedule' => ['cron' => '* * * * *']
                    ]
                ],
                [
                    ProcessConfigurationProvider::NODE_DEFINITIONS => [
                        'workflow_name_transition_one_schedule_process' => [
                            'label' => 'Scheduled transition "workflow_name_transition_one_schedule_process"',
                            'entity' => $this->workflowItemEntityClass,
                            'order'=> 0,
                            'exclude_definitions' => ['workflow_name_transition_one_schedule_process'],
                            'actions_configuration' => [
                                '@run_action_group' => [
                                    'action_group' => 'oro_workflow_transition_process_schedule',
                                    'parameters' => [
                                        'workflowName' => 'workflow_name',
                                        'transitionName' => 'transition_one'
                                    ]
                                ]
                            ],
                            'pre_conditions' => []
                        ],
                        'workflow_name_transition_three_schedule_process' => [
                            'label' => 'Scheduled transition "workflow_name_transition_three_schedule_process"',
                            'entity' => $this->workflowItemEntityClass,
                            'order' => 0,
                            'exclude_definitions' => ['workflow_name_transition_three_schedule_process'],
                            'actions_configuration' => [
                                '@run_action_group' => [
                                    'action_group' => 'oro_workflow_transition_process_schedule',
                                    'parameters' => [
                                        'workflowName' => 'workflow_name',
                                        'transitionName' => 'transition_three'
                                    ]
                                ]
                            ],
                            'pre_conditions' => []
                        ]
                    ],
                    ProcessConfigurationProvider::NODE_TRIGGERS => [
                        'workflow_name_transition_one_schedule_process' => [
                            ['cron' => '42 * * * *']
                        ],
                        'workflow_name_transition_three_schedule_process' => [
                            ['cron' => '* * * * *']
                        ]
                    ]
                ]
            ]
        ];
    }
}
