<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionSchedule;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\TriggerScheduleOptionsVerifier;

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
        $this->verifier = $this->getMockBuilder(
            'Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\TriggerScheduleOptionsVerifier'
        )->disableOriginalConstructor()->getMock();

        $this->workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->generator = new ProcessConfigurationGenerator($this->verifier, $this->workflowItemEntityClass);
    }

    /**
     * @dataProvider dataGenerateForScheduledTransition
     *
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
            $this->exactly(
                count(
                    array_filter(
                        $transitionConfigurations,
                        function ($v) {
                            return array_key_exists('schedule', $v);
                        }
                    )
                )
            )
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
                    'transition_one' => [
                        'schedule' => ['cron' => '42 * * * *']
                    ],
                    'transition_wont_be_managed' => [
                    ],
                    'transition_three' => [
                        'schedule' => ['cron' => '* * * * *']
                    ]
                ],
                [
                    ProcessConfigurationProvider::NODE_DEFINITIONS => [
                        'stpn__workflow_name__transition_one' => [
                            'label' => 'Scheduled transition "stpn__workflow_name__transition_one"',
                            'entity' => $this->workflowItemEntityClass,
                            'order' => 0,
                            'exclude_definitions' => ['stpn__workflow_name__transition_one'],
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
                        'stpn__workflow_name__transition_three' => [
                            'label' => 'Scheduled transition "stpn__workflow_name__transition_three"',
                            'entity' => $this->workflowItemEntityClass,
                            'order' => 0,
                            'exclude_definitions' => ['stpn__workflow_name__transition_three'],
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
                        'stpn__workflow_name__transition_one' => [
                            ['cron' => '42 * * * *']
                        ],
                        'stpn__workflow_name__transition_three' => [
                            ['cron' => '* * * * *']
                        ]
                    ]
                ]
            ]
        ];
    }
}
