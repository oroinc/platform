<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Bundle\ActionBundle\Model\ActionGroupServiceAdapter;
use Oro\Bundle\ActionBundle\Model\Assembler\ActionGroupAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\ParameterAssembler;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ServiceProviderInterface;

class ActionGroupAssemblerTest extends TestCase
{
    private ServiceProviderInterface|MockObject $actionGroupServiceLocator;
    private ParametersResolver|MockObject $parametersResolver;

    private ActionGroupAssembler $assembler;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionGroupServiceLocator = $this->createMock(ServiceProviderInterface::class);
        $this->parametersResolver = $this->createMock(ParametersResolver::class);

        $this->assembler = new ActionGroupAssembler(
            $this->createMock(ActionFactoryInterface::class),
            $this->createMock(ConditionFactory::class),
            new ParameterAssembler(),
            $this->parametersResolver,
            $this->actionGroupServiceLocator
        );
    }

    public function testAssembleWithServiceMinimal()
    {
        $service = new \stdClass();
        $this->actionGroupServiceLocator->expects($this->once())
            ->method('get')
            ->with('test_service')
            ->willReturn($service);

        $configuration = [
            'minimum_name' => [
                'service' => 'test_service'
            ]
        ];

        $definitions = $this->assembler->assemble($configuration);

        $expected = [
            'minimum_name' => new ActionGroupServiceAdapter(
                $this->parametersResolver,
                $service,
                'execute',
                null,
                null
            )
        ];
        $this->assertEquals($expected, $definitions);
    }

    public function testAssembleWithServiceAllParameters()
    {
        $service = new \stdClass();
        $this->actionGroupServiceLocator->expects($this->once())
            ->method('get')
            ->with('test_service')
            ->willReturn($service);

        $configuration = [
            'minimum_name' => [
                'service' => 'test_service',
                'method' => 'test_method',
                'return_value_name' => 'test_return_value',
                'parameters' => ['arg1' => []]
            ]
        ];

        $definitions = $this->assembler->assemble($configuration);

        $expected = [
            'minimum_name' => new ActionGroupServiceAdapter(
                $this->parametersResolver,
                $service,
                'test_method',
                'test_return_value',
                ['arg1' => []]
            )
        ];
        $this->assertEquals($expected, $definitions);
    }

    /**
     * @dataProvider assembleProvider
     */
    public function testAssemble(array $configuration, array $expected)
    {
        $definitions = $this->assembler->assemble($configuration);

        $this->assertEquals($expected, $definitions);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function assembleProvider(): array
    {
        $parameterAssembler = new ParameterAssembler();
        $parametersResolver = $this->createMock(ParametersResolver::class);
        $actionFactory = $this->createMock(ActionFactoryInterface::class);
        $conditionFactory = $this->createMock(ConditionFactory::class);

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
                            TestEntity1::class
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
}
