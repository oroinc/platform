<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionAssembler;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransitionAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formOptionsAssembler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $conditionFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionFactory;

    /**
     * @var TransitionAssembler
     */
    protected $assembler;

    /**
     * @var array
     */
    protected static $actions = [
        'preactions' => ['@assign_value' => ['parameters' => ['$attribute', 'preaction_value']]],
        'actions' => ['@assign_value' => ['parameters' => ['$attribute', 'action_value']]]
    ];

    /**
     * @var array
     */
    protected static $transitionDefinitions = [
        'empty_definition' => [],
        'with_preactions' => [
            'preactions' => ['@assign_value' => ['parameters' => ['$attribute', 'preaction_value']]]
        ],
        'with_pre_condition' => [
            'preconditions' => ['@true' => null]
        ],
        'with_condition' => [
            'conditions' => ['@true' => null]
        ],
        'with_actions' => [
            'actions' => ['@assign_value' => ['parameters' => ['$attribute', 'action_value']]]
        ],
        'full_definition' => [
            'page_template' => 'Test:Page:template',
            'dialog_template' => 'Test:Dialog:template',
            'preactions' => ['@assign_value' => ['parameters' => ['$attribute', 'preaction_value']]],
            'preconditions' => ['@true' => null],
            'conditions' => ['@true' => null],
            'actions' => ['@assign_value' => ['parameters' => ['$attribute', 'action_value']]],
        ]
    ];

    protected function setUp()
    {
        $this->formOptionsAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\FormOptionsAssembler')
            ->disableOriginalConstructor()
            ->setMethods(['assemble'])
            ->getMock();

        $this->conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionFactory = $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assembler = new TransitionAssembler(
            $this->formOptionsAssembler,
            $this->conditionFactory,
            $this->actionFactory
        );
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\AssemblerException
     * @dataProvider missedTransitionDefinitionDataProvider
     * @param array $configuration
     */
    public function testAssembleNoRequiredTransitionDefinitionException($configuration)
    {
        $this->assembler->assemble($configuration, [], [], []);
    }

    /**
     * @return array
     */
    public function missedTransitionDefinitionDataProvider()
    {
        return [
            'no options' => [
                [
                    'name' => []
                ]
            ],
            'no transition_definition' => [
                [
                    'name' => [
                        '' => 'test_transition'
                    ]
                ]
            ]
        ];
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\AssemblerException
     * @dataProvider incorrectTransitionDefinitionDataProvider
     * @param array $definitions
     */
    public function testUnknownTransitionDefinitionAssembler($definitions)
    {
        $configuration = [
            'test_transition' => [
                'transition_definition' => 'unknown'
            ]
        ];
        $this->assembler->assemble($configuration, $definitions, [], []);
    }

    /**
     * @return array
     */
    public function incorrectTransitionDefinitionDataProvider()
    {
        return [
            'no definitions' => [
                []
            ],
            'definitions as null' => [
                ['some' => null]
            ],
            'unknown definition' => [
                ['known' => []]
            ]
        ];
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\AssemblerException
     * @dataProvider incorrectStepsDataProvider
     * @param array $steps
     */
    public function testUnknownStepException($steps)
    {
        $configuration = [
            'test_transition' => [
                'transition_definition' => 'transition_definition',
                'label' => 'label',
                'step_to' => 'unknown'
            ]
        ];
        $definitions = ['transition_definition' => []];
        $this->assembler->assemble($configuration, $definitions, $steps, []);
    }

    /**
     * @return array
     */
    public function incorrectStepsDataProvider()
    {
        return [
            'no steps' => [
                []
            ],
            'unknown step' => [
                ['known' => $this->createStep()]
            ]
        ];
    }

    /**
     * @dataProvider configurationDataProvider
     *
     * @param array $configuration
     * @param array $transitionDefinition
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testAssemble(array $configuration, array $transitionDefinition)
    {
        $steps = [
            'target_step' => $this->createStep()
        ];

        $attributes = [
            'attribute' => $this->createAttribute()
        ];

        $expectedPreAction      = null;
        $expectedCondition      = null;
        $expectedPreCondition   = $this->createCondition();
        $expectedPostAction     = null;
        $defaultAclPrecondition = [];
        $preConditions          = [];

        if (isset($configuration['acl_resource'])) {
            $defaultAclPrecondition = [
                '@acl_granted' => [
                    'parameters' => [$configuration['acl_resource']]
                ]
            ];

            if (isset($configuration['acl_message'])) {
                $defaultAclPrecondition['@acl_granted']['message'] = $configuration['acl_message'];
            }
        }

        if (isset($transitionDefinition['preconditions']) && $defaultAclPrecondition) {
            $preConditions = [
                '@and' => [
                    $defaultAclPrecondition,
                    $transitionDefinition['preconditions']
                ]
            ];
        } elseif (isset($transitionDefinition['preconditions'])) {
            $preConditions = $transitionDefinition['preconditions'];
        }

        $count = 0;

        if ($preConditions) {
            $this->conditionFactory->expects($this->at($count))
                ->method('create')
                ->with(ConfigurableCondition::ALIAS, $preConditions)
                ->will($this->returnValue($expectedPreCondition));
            $count++;
        }
        $this->conditionFactory->expects($this->at($count))
            ->method('create')
            ->with(
                ConfigurableCondition::ALIAS,
                ['@is_granted_workflow_transition' => ['parameters' => ['test_transition', 'target_step']]]
            )
            ->will($this->returnValue($expectedPreCondition));
        $count++;

        if (array_key_exists('conditions', $transitionDefinition)) {
            $expectedCondition = $this->createCondition();
            $this->conditionFactory->expects($this->at($count))
                ->method('create')
                ->with(ConfigurableCondition::ALIAS, $transitionDefinition['conditions'])
                ->will($this->returnValue($expectedCondition));
        }

        $this->actionFactory->expects($this->any())
            ->method('create')
            ->with(ConfigurableAction::ALIAS, self::isType('array'))
            ->willReturnCallback(
                function ($type, $config) use (&$expectedPreAction, &$expectedPostAction) {
                    $action = $this->createAction();

                    if ($config === self::$actions['preactions']) {
                        $expectedPreAction = $action;
                    }

                    if ($config === self::$actions['actions']) {
                        $expectedPostAction = $action;
                    }

                    return $action;
                }
            );

        $this->formOptionsAssembler->expects($this->once())
            ->method('assemble')
            ->with(
                isset($configuration['form_options']) ? $configuration['form_options'] : [],
                $attributes,
                'transition',
                'test_transition'
            )
            ->will($this->returnArgument(0));

        $transitions = $this->assembler->assemble(
            ['test_transition' => $configuration],
            self::$transitionDefinitions,
            $steps,
            $attributes
        );

        $configuration = array_merge(
            [
                'is_start' => false,
                'form_type' => WorkflowTransitionType::NAME,
                'form_options' => [],
                'frontend_options' => [],
            ],
            $configuration
        );

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $transitions);
        $this->assertCount(1, $transitions);
        $this->assertTrue($transitions->containsKey('test_transition'));

        /** @var Transition $actualTransition */
        $actualTransition = $transitions->get('test_transition');
        $this->assertEquals('test_transition', $actualTransition->getName(), 'Incorrect name');
        $this->assertEquals($steps['target_step'], $actualTransition->getStepTo(), 'Incorrect step_to');

        $expectedDisplayType = WorkflowConfiguration::DEFAULT_TRANSITION_DISPLAY_TYPE;

        if (isset($configuration['display_type'])) {
            $expectedDisplayType = $configuration['display_type'];
        }

        $this->assertEquals($expectedDisplayType, $actualTransition->getDisplayType(), 'Incorrect display type');
        $this->assertEquals(
            $configuration['frontend_options'],
            $actualTransition->getFrontendOptions(),
            'Incorrect frontend_options'
        );
        $this->assertEquals($configuration['is_start'], $actualTransition->isStart(), 'Incorrect is_start');
        $this->assertEquals($configuration['form_type'], $actualTransition->getFormType(), 'Incorrect form_type');
        $this->assertEquals(
            $configuration['form_options'],
            $actualTransition->getFormOptions(),
            'Incorrect form_options'
        );

        $initEntities = array_key_exists(WorkflowConfiguration::NODE_INIT_ENTITIES, $configuration)
            ? $configuration[WorkflowConfiguration::NODE_INIT_ENTITIES]
            : [];
        $this->assertEquals($initEntities, $actualTransition->getInitEntities());

        $this->assertTemplate('page', $configuration, $actualTransition);
        $this->assertTemplate('dialog', $configuration, $actualTransition);

        $this->assertNotNull($actualTransition->getPreCondition(), 'Incorrect Precondition');
        $this->assertEquals($expectedPreCondition, $actualTransition->getPreCondition(), 'Incorrect Precondition');

        if (array_key_exists('schedule', $configuration)) {
            $scheduleDefinition = $configuration['schedule'];
            $this->assertEquals((string) $scheduleDefinition['cron'], $actualTransition->getScheduleCron());
            if (isset($scheduleDefinition['filter'])) {
                $this->assertEquals((string) $scheduleDefinition['filter'], $actualTransition->getScheduleFilter());
            }
        }

        $this->assertSame($expectedCondition, $actualTransition->getCondition(), 'Incorrect condition');
        $this->assertSame($expectedPreAction, $actualTransition->getPreAction(), 'Incorrect preaction');

        $this->assertSame($expectedPostAction, $actualTransition->getAction(), 'Incorrect action');
    }

    /**
     * @param string $templateType
     * @param array $configuration
     * @param $actualTransition
     */
    protected function assertTemplate($templateType, $configuration, $actualTransition)
    {
        $configKey = $templateType . '_template';
        $getter    = 'get' . ucfirst($templateType) . 'Template';

        if (array_key_exists($configKey, $configuration)) {
            $this->assertEquals($configuration[$configKey], $actualTransition->$getter());
        } else {
            $this->assertNull($actualTransition->$getter());
        }
    }

    /**
     * @return array
     */
    public function configurationDataProvider()
    {
        return [
            'empty_definition' => [
                'configuration' => [
                    'transition_definition' => 'empty_definition',
                    'label' => 'label',
                    'step_to' => 'target_step',
                    'form_type' => 'custom_workflow_transition',
                    'display_type' => 'page',
                    'form_options' => [
                        'attribute_fields' => [
                            'attribute_on_be' => ['type' => 'text']
                        ]
                    ],
                    'frontend_options' => ['class' => 'foo', 'icon' => 'bar'],
                ],
                'transitionDefinition' => self::$transitionDefinitions['empty_definition'],
            ],
            'with_condition' => [
                'configuration' => [
                    'transition_definition' => 'with_condition',
                    'step_to' => 'target_step',
                ],
                'transitionDefinition' => self::$transitionDefinitions['with_condition'],
            ],
            'with_preactions' => [
                'configuration' => [
                    'transition_definition' => 'with_condition',
                    'step_to' => 'target_step',
                ],
                'transitionDefinition' => self::$transitionDefinitions['with_preactions'],
            ],
            'with_actions' => [
                'configuration' => [
                    'transition_definition' => 'with_actions',
                    'step_to' => 'target_step',
                ],
                'transitionDefinition' => self::$transitionDefinitions['with_actions'],
            ],
            'with init context' => [
                'configuration' => [
                    'transition_definition' => 'empty_definition',
                    'init_entities' => ['entity1', 'entity2'],
                    'init_routes' => ['route1', 'route2'],
                    'step_to' => 'step',
                ],
                'transitionDefinition' => self::$transitionDefinitions['empty_definition'],
            ]
        ];
    }

    public function testAssembleWithIsTrueCondition()
    {
        $configuration = [
            'transition_definition' => 'with_condition',
            'step_to' => 'target_step',
        ];
        $transitionDefinition = self::$transitionDefinitions['with_condition'];

        $steps      = ['target_step' => $this->createStep()];
        $attributes = ['attribute' => $this->createAttribute()];

        $expectedCondition = $expectedPreCondition   = $this->createCondition();

        $this->conditionFactory->expects($this->at(0))->method('create')
            ->with(
                ConfigurableCondition::ALIAS,
                ['@is_granted_workflow_transition' => ['parameters' => ['test_transition', 'target_step']]]
            )
            ->will($this->returnValue($expectedPreCondition));
        $this->conditionFactory->expects($this->at(1))->method('create')
            ->with(ConfigurableCondition::ALIAS, $transitionDefinition['conditions'])
            ->will($this->returnValue($expectedCondition));
        $this->conditionFactory->expects($this->exactly(2))->method('create');

        $this->formOptionsAssembler->expects($this->once())->method('assemble')
            ->with([], $attributes, 'transition', 'test_transition')
            ->will($this->returnArgument(0));

        $transitions = $this->assembler->assemble(
            ['test_transition' => $configuration],
            self::$transitionDefinitions,
            $steps,
            $attributes
        );

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $transitions);
        $this->assertCount(1, $transitions);
        $this->assertTrue($transitions->containsKey('test_transition'));

        /** @var Transition $actualTransition */
        $actualTransition = $transitions->get('test_transition');
        $this->assertEquals('test_transition', $actualTransition->getName(), 'Incorrect name');
        $this->assertEquals($steps['target_step'], $actualTransition->getStepTo(), 'Incorrect step_to');

        $this->assertEquals(
            WorkflowConfiguration::DEFAULT_TRANSITION_DISPLAY_TYPE,
            $actualTransition->getDisplayType(),
            'Incorrect display type'
        );
        $this->assertEmpty($actualTransition->getFrontendOptions());
        $this->assertFalse($actualTransition->isStart());
        $this->assertEquals('oro_workflow_transition', $actualTransition->getFormType());
        $this->assertEmpty($actualTransition->getFormOptions());

        $configuration = array_merge(
            [
                'is_start' => false,
                'form_type' => WorkflowTransitionType::NAME,
                'form_options' => [],
                'frontend_options' => [],
            ],
            $configuration
        );
        $this->assertTemplate('page', $configuration, $actualTransition);
        $this->assertTemplate('dialog', $configuration, $actualTransition);

        $this->assertNotNull($actualTransition->getCondition());
        $this->assertSame($expectedCondition, $actualTransition->getCondition(), 'Incorrect condition');

        $this->assertNotNull($actualTransition->getPreCondition());
        $this->assertEquals($expectedPreCondition, $actualTransition->getPreCondition(), 'Incorrect Precondition');

        $this->assertNull($actualTransition->getPreAction());
        $this->assertNull($actualTransition->getAction());
    }

    public function testAssembleWithFullDefinition()
    {
        $configuration = [
            'transition_definition' => 'full_definition',
            'acl_resource' => 'test_acl',
            'acl_message' => 'test acl message',
            'step_to' => 'target_step',
            'schedule' => ['cron' => '1 * * * *', 'filter' => 'e.field < 1']
        ];
        $transitionDefinition = self::$transitionDefinitions['full_definition'];

        $steps = ['target_step' => $this->createStep()];
        $attributes = ['attribute' => $this->createAttribute()];
        $expectedPreAction = $expectedPostAction = null;
        $expectedCondition = $expectedPreCondition = $this->createCondition();

        $preConditions = [
            '@and' => [
                ['@is_granted_workflow_transition' => ['parameters' => ['test_transition', 'target_step']]],
                ['@and' => [
                    ['@acl_granted' => [
                        'parameters' => [$configuration['acl_resource']], 'message' => $configuration['acl_message']
                    ]],
                    ['@true' => null]
                ]]
            ]
        ];
        $this->conditionFactory->expects($this->at(0))->method('create')
            ->with(ConfigurableCondition::ALIAS, $preConditions)
            ->will($this->returnValue($expectedPreCondition));
        $this->conditionFactory->expects($this->at(1))->method('create')
            ->with(ConfigurableCondition::ALIAS, $transitionDefinition['conditions'])
            ->will($this->returnValue($expectedCondition));
        $this->conditionFactory->expects($this->exactly(2))->method('create');

        $this->actionFactory->expects($this->exactly(2))
            ->method('create')
            ->with(ConfigurableAction::ALIAS, self::isType('array'))
            ->willReturnCallback(function ($type, $config) use (&$expectedPreAction, &$expectedPostAction) {
                $action = $this->createAction();
                if ($config === self::$actions['preactions']) {
                    $expectedPreAction = $action;
                }
                if ($config === self::$actions['actions']) {
                    $expectedPostAction = $action;
                }
                return $action;
            });

        $this->formOptionsAssembler->expects($this->once())->method('assemble')
            ->with([], $attributes, 'transition', 'test_transition')
            ->will($this->returnArgument(0));

        $transitions = $this->assembler->assemble(
            ['test_transition' => $configuration],
            self::$transitionDefinitions,
            $steps,
            $attributes
        );

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $transitions);
        $this->assertCount(1, $transitions);
        $this->assertTrue($transitions->containsKey('test_transition'));

        /** @var Transition $actualTransition */
        $actualTransition = $transitions->get('test_transition');
        $this->assertEquals('test_transition', $actualTransition->getName(), 'Incorrect name');
        $this->assertEquals($steps['target_step'], $actualTransition->getStepTo(), 'Incorrect step_to');

        $this->assertEmpty($actualTransition->getFrontendOptions());
        $this->assertFalse($actualTransition->isStart());
        $this->assertEquals(WorkflowTransitionType::NAME, $actualTransition->getFormType());
        $this->assertEquals([], $actualTransition->getFormOptions());

        $this->assertTemplate('page', $configuration, $actualTransition);
        $this->assertTemplate('dialog', $configuration, $actualTransition);

        $this->assertNotNull($actualTransition->getPreCondition(), 'Incorrect Precondition');
        $this->assertEquals($expectedPreCondition, $actualTransition->getPreCondition(), 'Incorrect Precondition');

        $scheduleDefinition = $configuration['schedule'];
        $this->assertEquals((string) $scheduleDefinition['cron'], $actualTransition->getScheduleCron());
        $this->assertEquals((string) $scheduleDefinition['filter'], $actualTransition->getScheduleFilter());

        $this->assertSame($expectedCondition, $actualTransition->getCondition(), 'Incorrect condition');
        $this->assertSame($expectedPreAction, $actualTransition->getPreAction(), 'Incorrect preaction');
        $this->assertSame($expectedPostAction, $actualTransition->getAction(), 'Incorrect action');
    }

    public function testAssembleWithEmptyDefinition()
    {
        $configuration = [
            'transition_definition' => 'empty_definition',
            'acl_resource' => 'test_acl',
            'acl_message' => 'test acl message',
            'step_to' => 'target_step',
            'is_start' => true,
        ];

        $steps = ['target_step' => $this->createStep()];
        $attributes = ['attribute' => $this->createAttribute()];

        $expectedPreCondition   = $this->createCondition();

        $preConditions = [
            '@and' => [
                ['@is_granted_workflow_transition' => ['parameters' => ['test_transition', 'target_step']]],
                ['@acl_granted' => [
                    'parameters' => [$configuration['acl_resource']],
                    'message' => $configuration['acl_message']
                ]]
            ]
        ];
        $this->conditionFactory->expects($this->once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $preConditions)
            ->will($this->returnValue($expectedPreCondition));

        $this->actionFactory->expects($this->never())->method('create');

        $this->formOptionsAssembler->expects($this->once())
            ->method('assemble')
            ->with([], $attributes, 'transition', 'test_transition')
            ->will($this->returnArgument(0));

        $transitions = $this->assembler->assemble(
            ['test_transition' => $configuration],
            self::$transitionDefinitions,
            $steps,
            $attributes
        );

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $transitions);
        $this->assertCount(1, $transitions);
        $this->assertTrue($transitions->containsKey('test_transition'));

        /** @var Transition $actualTransition */
        $actualTransition = $transitions->get('test_transition');

        $this->assertEquals('test_transition', $actualTransition->getName(), 'Incorrect name');
        $this->assertEquals($steps['target_step'], $actualTransition->getStepTo(), 'Incorrect step_to');
        $this->assertEquals(
            WorkflowConfiguration::DEFAULT_TRANSITION_DISPLAY_TYPE,
            $actualTransition->getDisplayType(),
            'Incorrect display type'
        );

        $this->assertEquals([], $actualTransition->getFrontendOptions(), 'Incorrect frontend_options');
        $this->assertTrue($actualTransition->isStart(), 'Incorrect is_start');

        $this->assertEquals(WorkflowTransitionType::NAME, $actualTransition->getFormType(), 'Incorrect form_type');
        $this->assertEmpty($actualTransition->getFormOptions(), 'Incorrect form_options');

        $this->assertTemplate('page', $configuration, $actualTransition);
        $this->assertTemplate('dialog', $configuration, $actualTransition);

        $this->assertNotNull($actualTransition->getPreCondition(), 'Incorrect Precondition');
        $this->assertEquals($expectedPreCondition, $actualTransition->getPreCondition(), 'Incorrect Precondition');

        $this->assertNull($actualTransition->getCondition(), 'Incorrect condition');
        $this->assertNull($actualTransition->getPreAction(), 'Incorrect preaction');
        $this->assertNull($actualTransition->getAction(), 'Incorrect action');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Step
     */
    protected function createStep()
    {
        return $this->getMockBuilder(Step::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Attribute
     */
    protected function createAttribute()
    {
        return $this->getMockBuilder(Attribute::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExpressionInterface
     */
    protected function createCondition()
    {
        return $this->getMock(ExpressionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ActionInterface
     */
    protected function createAction()
    {
        return $this->getMock(ActionInterface::class);
    }
}
