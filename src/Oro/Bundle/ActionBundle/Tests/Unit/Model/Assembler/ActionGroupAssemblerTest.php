<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Bundle\ActionBundle\Model\Assembler\ActionGroupAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\ArgumentAssembler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionGroupAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActionGroupAssembler */
    protected $assembler;

    protected function setUp()
    {
        $this->assembler = new ActionGroupAssembler(
            $this->getActionFactory(),
            $this->getConditionFactory(),
            $this->getArgumentAssembler(),
            $this->getDoctrineHelper()
        );
    }

    protected function tearDown()
    {
        unset($this->assembler);
    }

    /**
     * @param array $configuration
     * @param array $expected
     *
     * @dataProvider assembleProvider
     */
    public function testAssemble(array $configuration, array $expected)
    {
        $definitions = $this->assembler->assemble($configuration);

        $this->assertEquals($expected, $definitions);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function assembleProvider()
    {
        $argumentAssembler = $this->getArgumentAssembler();
        $actionFactory = $this->getActionFactory();
        $conditionFactory = $this->getConditionFactory();
        $doctrineHelper = $this->getDoctrineHelper();

        $definition1 = new ActionGroupDefinition();
        $definition1
            ->setName('minimum_name')
            ->setConditions([])
            ->setActions([]);

        $definition2 = clone $definition1;
        $definition2
            ->setName('maximum_name')
            ->setArguments([
                'arg1' => [],
                'arg2' => [
                    'required' => true,
                ],
                'arg3' => [
                    'required' => true,
                    'type' => 'string',
                    'message' => 'Error Message',
                ],
            ])
            ->setConditions([
                '@and' => [
                    [
                        '@has_value' => [
                            'parameters' => ['$arg2'],
                            'message' => '$arg2 is required',
                        ]
                    ],
                    [
                        '@has_value' => [
                            'parameters' => ['$arg3'],
                            'message' => 'Error Message: $arg3 is required',
                        ]
                    ],
                    [
                        '@type' => [
                            'parameters' => ['$arg3', 'string'],
                            'message' => 'Error Message: $arg3 must be of type "{{ type }}", "{{ value }}" given',
                        ]
                    ],
                    ['@condition' => 'config_conditions'],
                ]
            ])
            ->setActions(['config_actions']);

        $definition3 = clone $definition2;
        $definition3
            ->setName('maximum_name_and_acl')
            ->setConditions([
                '@and' => [
                    ['@acl_granted' => 'test_acl'],
                    [
                        '@has_value' => [
                            'parameters' => ['$arg2'],
                            'message' => '$arg2 is required',
                        ]
                    ],
                    [
                        '@has_value' => [
                            'parameters' => ['$arg3'],
                            'message' => 'Error Message: $arg3 is required',
                        ]
                    ],
                    [
                        '@type' => [
                            'parameters' => ['$arg3', 'string'],
                            'message' => 'Error Message: $arg3 must be of type "{{ type }}", "{{ value }}" given',
                        ]
                    ],
                    ['@condition' => 'config_conditions']
                ]
             ])
            ->setActions(['config_actions']);

        return [
            'no data' => [
                [],
                'expected' => [],
            ],
            'minimum data' => [
                [
                    'minimum_name' => [
                        'label' => 'My Label',
                        'entities' => [
                            '\Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'
                        ],
                    ]
                ]
                ,
                'expected' => [
                    'minimum_name' => new ActionGroup(
                        $actionFactory,
                        $conditionFactory,
                        $argumentAssembler,
                        $definition1
                    )
                ],
            ],
            'maximum data' => [
                [
                    'maximum_name' => [
                        'arguments' => [
                            'arg1' => [],
                            'arg2' => [
                                'required' => true,
                            ],
                            'arg3' => [
                                'required' => true,
                                'type' => 'string',
                                'message' => 'Error Message',
                            ],
                        ],
                        'conditions' => [
                            '@condition' => 'config_conditions',
                        ],
                        'actions' => ['config_actions'],
                    ]
                ],
                'expected' => [
                    'maximum_name' => new ActionGroup(
                        $actionFactory,
                        $conditionFactory,
                        $argumentAssembler,
                        $definition2
                    )
                ],
            ],
            'maximum data and acl_resource' => [
                [
                    'maximum_name_and_acl' => [
                        'arguments' => [
                            'arg1' => [],
                            'arg2' => [
                                'required' => true,
                            ],
                            'arg3' => [
                                'required' => true,
                                'type' => 'string',
                                'message' => 'Error Message',
                            ],
                        ],
                        'conditions' => ['@condition' => 'config_conditions'],
                        'actions' => ['config_actions'],
                        'acl_resource' => 'test_acl',
                    ]
                ],
                'expected' => [
                    'maximum_name_and_acl' => new ActionGroup(
                        $actionFactory,
                        $conditionFactory,
                        $argumentAssembler,
                        $definition3
                    )
                ],
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ActionFactory
     */
    protected function getActionFactory()
    {
        return $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConditionFactory
     */
    protected function getConditionFactory()
    {
        return $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ArgumentAssembler
     */
    protected function getArgumentAssembler()
    {
        return new ArgumentAssembler();
    }
}
