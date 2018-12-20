<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Bundle\ActionBundle\Model\Assembler\ActionGroupAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\ParameterAssembler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionGroupAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActionGroupAssembler */
    protected $assembler;

    protected function setUp()
    {
        $this->assembler = new ActionGroupAssembler(
            $this->getActionFactory(),
            $this->getConditionFactory(),
            $this->getParameterAssembler(),
            $this->getParametersResolver()
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
        $parameterAssembler = $this->getParameterAssembler();
        $parametersResolver = $this->getParametersResolver();
        $actionFactory = $this->getActionFactory();
        $conditionFactory = $this->getConditionFactory();

        $definition1 = new ActionGroupDefinition();
        $definition1
            ->setName('minimum_name')
            ->setConditions([])
            ->setActions([]);

        $definition2 = clone $definition1;
        $definition2
            ->setName('maximum_name')
            ->setParameters([
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
                        ]
                    ]
                ]
                ,
                'expected' => [
                    'minimum_name' => new ActionGroup(
                        $actionFactory,
                        $conditionFactory,
                        $parameterAssembler,
                        $parametersResolver,
                        $definition1
                    )
                ],
            ],
            'maximum data' => [
                [
                    'maximum_name' => [
                        'conditions' => [
                            '@condition' => 'config_conditions',
                        ],
                        'actions' => ['config_actions'],
                        'parameters' => [
                            'arg1' => [],
                            'arg2' => [
                                'required' => true,
                            ],
                            'arg3' => [
                                'required' => true,
                                'type' => 'string',
                                'message' => 'Error Message',
                            ],
                        ]
                    ]
                ],
                'expected' => [
                    'maximum_name' => new ActionGroup(
                        $actionFactory,
                        $conditionFactory,
                        $parameterAssembler,
                        $parametersResolver,
                        $definition2
                    )
                ],
            ],
            'maximum data and acl_resource' => [
                [
                    'maximum_name_and_acl' => [
                        'parameters' => [
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
                        $parameterAssembler,
                        $parametersResolver,
                        $definition3
                    )
                ],
            ],
        ];
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ActionFactory
     */
    protected function getActionFactory()
    {
        return $this->createMock('Oro\Component\Action\Action\ActionFactoryInterface');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ConditionFactory
     */
    protected function getConditionFactory()
    {
        return $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ParameterAssembler
     */
    protected function getParameterAssembler()
    {
        return new ParameterAssembler();
    }

    /**
     * @return ActionGroup\ParametersResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getParametersResolver()
    {
        return $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
