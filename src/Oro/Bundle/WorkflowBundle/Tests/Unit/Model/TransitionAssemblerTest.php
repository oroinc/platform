<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionAssembler;

use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;

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
    protected static $transitionDefinitions = array(
        'empty_definition' => array(),
        'with_pre_condition' => array(
            'pre_conditions' => array('@true' => null)
        ),
        'with_condition' => array(
            'conditions' => array('@true' => null)
        ),
        'with_post_actions' => array(
            'post_actions' => array('@assign_value' => array('parameters' => array('$attribute', 'first_value')))
        ),
        'full_definition' => array(
            'page_template' => 'Test:Page:template',
            'dialog_template' => 'Test:Dialog:template',
            'pre_conditions' => array('@true' => null),
            'conditions' => array('@true' => null),
            'post_actions' => array('@assign_value' => array('parameters' => array('$attribute', 'first_value'))),
        )
    );

    protected function setUp()
    {
        $this->formOptionsAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\FormOptionsAssembler')
            ->disableOriginalConstructor()
            ->setMethods(array('assemble'))
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
        $this->assembler->assemble($configuration, array(), array(), array());
    }

    public function missedTransitionDefinitionDataProvider()
    {
        return array(
            'no options' => array(
                array(
                    'name' => array()
                )
            ),
            'no transition_definition' => array(
                array(
                    'name' => array(
                        '' => 'test'
                    )
                )
            )
        );
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\AssemblerException
     * @dataProvider incorrectTransitionDefinitionDataProvider
     * @param array $definitions
     */
    public function testUnknownTransitionDefinitionAssembler($definitions)
    {
        $configuration = array(
            'test' => array(
                'transition_definition' => 'unknown'
            )
        );
        $this->assembler->assemble($configuration, $definitions, array(), array());
    }

    public function incorrectTransitionDefinitionDataProvider()
    {
        return array(
            'no definitions' => array(
                array()
            ),
            'definitions as null' => array(
                array('some' => null)
            ),
            'unknown definition' => array(
                array('known' => array())
            )
        );
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\AssemblerException
     * @dataProvider incorrectStepsDataProvider
     * @param array $steps
     */
    public function testUnknownStepException($steps)
    {
        $configuration = array(
            'test' => array(
                'transition_definition' => 'transition_definition',
                'label' => 'label',
                'step_to' => 'unknown'
            )
        );
        $definitions = array('transition_definition' => array());
        $this->assembler->assemble($configuration, $definitions, $steps, array());
    }

    public function incorrectStepsDataProvider()
    {
        return array(
            'no steps' => array(
                array()
            ),
            'unknown step' => array(
                array('known' => $this->createStep())
            )
        );
    }

    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param array $transitionDefinition
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAssemble(array $configuration, array $transitionDefinition)
    {
        $steps = array(
            'step' => $this->createStep()
        );

        $attributes = array(
            'attribute' => $this->createAttribute()
        );

        $expectedCondition      = null;
        $expectedPreCondition   = $this->createCondition();
        $expectedAction         = null;
        $defaultAclPrecondition = array();
        $preConditions          = array();

        if (isset($configuration['acl_resource'])) {
            $defaultAclPrecondition = array(
                '@acl_granted' => array(
                    'parameters' => array($configuration['acl_resource'])
                )
            );

            if (isset($configuration['acl_message'])) {
                $defaultAclPrecondition['@acl_granted']['message'] = $configuration['acl_message'];
            }
        }

        if (isset($transitionDefinition['pre_conditions']) && $defaultAclPrecondition) {
            $preConditions = array(
                '@and' => array(
                    $defaultAclPrecondition,
                    $transitionDefinition['pre_conditions']
                )
            );
        } elseif (isset($transitionDefinition['pre_conditions'])) {
            $preConditions = $transitionDefinition['pre_conditions'];
        }

        $count = 0;

        if ($preConditions) {
            $this->conditionFactory->expects($this->at($count))
                ->method('create')
                ->with(ConfigurableCondition::ALIAS, $preConditions)
                ->will($this->returnValue($expectedPreCondition));
            $count++;
        }

        if (array_key_exists('conditions', $transitionDefinition)) {
            $expectedCondition = $this->createCondition();
            $this->conditionFactory->expects($this->at($count))
                ->method('create')
                ->with(ConfigurableCondition::ALIAS, $transitionDefinition['conditions'])
                ->will($this->returnValue($expectedCondition));
        }

        $actionFactoryCallCount = 0;

        if (array_key_exists('post_actions', $transitionDefinition)) {
            $actionFactoryCallCount++;
        }

        if (array_key_exists('init_actions', $transitionDefinition)) {
            $actionFactoryCallCount++;
        }

        if ($actionFactoryCallCount) {
            $expectedAction = $this->createAction();
            $this->actionFactory->expects($this->exactly($actionFactoryCallCount))
                ->method('create')
                ->with(ConfigurableAction::ALIAS, $transitionDefinition['post_actions'])
                ->will($this->returnValue($this->createAction()));
        }

        $this->formOptionsAssembler->expects($this->once())
            ->method('assemble')
            ->with(
                isset($configuration['form_options']) ? $configuration['form_options'] : array(),
                $attributes,
                'transition',
                'test'
            )
            ->will($this->returnArgument(0));

        $transitions = $this->assembler->assemble(
            array('test' => $configuration),
            self::$transitionDefinitions,
            $steps,
            $attributes
        );

        $configuration = array_merge(
            array(
                'is_start' => false,
                'form_type' => WorkflowTransitionType::NAME,
                'form_options' => array(),
                'frontend_options' => array(),
            ),
            $configuration
        );

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $transitions);
        $this->assertCount(1, $transitions);
        $this->assertTrue($transitions->containsKey('test'));

        /** @var Transition $actualTransition */
        $actualTransition = $transitions->get('test');
        $this->assertEquals('test', $actualTransition->getName(), 'Incorrect name');
        $this->assertEquals($steps['step'], $actualTransition->getStepTo(), 'Incorrect step_to');
        $this->assertEquals($configuration['label'], $actualTransition->getLabel(), 'Incorrect label');

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

        $this->assertTemplate('page', $configuration, $actualTransition);
        $this->assertTemplate('dialog', $configuration, $actualTransition);

        if ($preConditions) {
            $this->assertEquals($expectedPreCondition, $actualTransition->getPreCondition(), 'Incorrect Precondition');
        } else {
            $this->assertNull($actualTransition->getPreCondition(), 'Incorrect Precondition');
        }

        if (array_key_exists('schedule', $configuration)) {
            $scheduleDefinition = $configuration['schedule'];
            $this->assertEquals((string) $scheduleDefinition['cron'], $actualTransition->getScheduleCron());
            if (isset($scheduleDefinition['filter'])) {
                $this->assertEquals((string) $scheduleDefinition['filter'], $actualTransition->getScheduleFilter());
            }
        }

        $this->assertEquals($expectedCondition, $actualTransition->getCondition(), 'Incorrect condition');
        $this->assertEquals($expectedAction, $actualTransition->getPostAction(), 'Incorrect post_action');
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
        return array(
            'empty_definition' => array(
                'configuration' => array(
                    'transition_definition' => 'empty_definition',
                    'label' => 'label',
                    'step_to' => 'step',
                    'form_type' => 'custom_workflow_transition',
                    'display_type' => 'page',
                    'form_options' => array(
                        'attribute_fields' => array(
                            'attribute_on_be' => array('type' => 'text')
                        )
                    ),
                    'frontend_options' => array('class' => 'foo', 'icon' => 'bar'),
                ),
                'transitionDefinition' => self::$transitionDefinitions['empty_definition'],
            ),
            'with_condition' => array(
                'configuration' => array(
                    'transition_definition' => 'with_condition',
                    'label' => 'label',
                    'step_to' => 'step',
                ),
                'transitionDefinition' => self::$transitionDefinitions['with_condition'],
            ),
            'with_post_actions' => array(
                'configuration' => array(
                    'transition_definition' => 'with_post_actions',
                    'label' => 'label',
                    'step_to' => 'step',
                ),
                'transitionDefinition' => self::$transitionDefinitions['with_post_actions'],
            ),
            'full_definition' => array(
                'configuration' => array(
                    'transition_definition' => 'full_definition',
                    'acl_resource' => 'test_acl',
                    'acl_message' => 'test acl message',
                    'label' => 'label',
                    'step_to' => 'step',
                    'schedule' => [
                        'cron' => '1 * * * *',
                        'filter' => 'e.field < 1'
                    ],
                ),
                'transitionDefinition' => self::$transitionDefinitions['full_definition'],
            ),
            'start_transition' => array(
                'configuration' => array(
                    'transition_definition' => 'empty_definition',
                    'acl_resource' => 'test_acl',
                    'acl_message' => 'test acl message',
                    'label' => 'label',
                    'step_to' => 'step',
                    'is_start' => true,
                ),
                'transitionDefinition' => self::$transitionDefinitions['empty_definition'],
            ),
        );
    }

    protected function createStep()
    {
        return $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Step')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createAttribute()
    {
        return $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Attribute')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createCondition()
    {
        return $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionInterface')
            ->getMockForAbstractClass();
    }

    protected function createAction()
    {
        return $this->getMockBuilder('Oro\Component\Action\Action\ActionInterface')
            ->getMockForAbstractClass();
    }
}
