<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionAssembler;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\TreeExecutor;
use Oro\Component\Action\Tests\Unit\Action\Stub\ArrayAction;
use Oro\Component\Action\Tests\Unit\Action\Stub\ArrayCondition;
use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;
use Oro\Component\Testing\ReflectionUtil;

class ActionAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider assembleDataProvider
     */
    public function testAssemble(array $source, array $expected)
    {
        $actionFactory = $this->createMock(ActionFactoryInterface::class);
        $actionFactory->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($type, $options, $condition) {
                if (TreeExecutor::ALIAS === $type) {
                    $action = $this->getTreeExecutor();
                } else {
                    $action = new ArrayAction(['_type' => $type]);
                    $action->initialize($options);
                }
                if ($condition) {
                    $action->setCondition($condition);
                }

                return $action;
            });

        $conditionFactory = $this->getMockBuilder(ConditionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $conditionFactory->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($type, $options) {
                $condition = new ArrayCondition(['_type' => $type]);
                $condition->initialize($options);

                return $condition;
            });

        $configurationPass = $this->createMock(ConfigurationPassInterface::class);
        $configurationPass->expects($this->any())
            ->method('passConfiguration')
            ->with($this->isType('array'))
            ->willReturnCallback(function (array $data) {
                $data['_pass'] = true;

                return $data;
            });

        $assembler = new ActionAssembler($actionFactory, $conditionFactory);
        $assembler->addConfigurationPass($configurationPass);
        /** @var TreeExecutor $actualTree */
        $actualTree = $assembler->assemble($source);
        $this->assertInstanceOf(TreeExecutor::class, $actualTree);
        $this->assertEquals($expected, $this->getActions($actualTree));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function assembleDataProvider(): array
    {
        return [
            'empty configuration' => [
                'source'   => [],
                'expected' => [],
            ],
            'not empty configuration' => [
                'source' => [
                    [
                        '@create_new_entity' => [
                            'parameters' => ['class_name' => 'TestClass'],
                        ],
                    ],
                    [
                        '@assign_value' => [
                            'parameters' => ['from' => 'name', 'to' => 'contact.name'],
                            'break_on_failure' => true,
                        ]
                    ],
                    ['not_a_service' => []]
                ],
                'expected' => [
                    [
                        'instance' => [
                            '_type' => 'create_new_entity',
                            'parameters' => ['class_name' => 'TestClass', '_pass' => true]
                        ],
                        'breakOnFailure' => true,
                    ],
                    [
                        'instance' => [
                            '_type' => 'assign_value',
                            'parameters' => ['from' => 'name', 'to' => 'contact.name', '_pass' => true]
                        ],
                        'breakOnFailure' => true,
                    ],
                ],
            ],
            'nested configuration' => [
                'source' => [
                    [
                        '@tree' => [
                            [
                                '@assign_value' => [
                                    'parameters' => ['from' => 'name', 'to' => 'contact.name'],
                                    'break_on_failure' => true,
                                ]
                            ],
                        ]
                    ],
                    [
                        '@tree' => [
                            'actions' => [
                                [
                                    '@assign_value' => [
                                        'parameters' => ['from' => 'date', 'to' => 'contact.date'],
                                        'break_on_failure' => false,
                                    ]
                                ],
                            ],
                        ]
                    ],
                ],
                'expected' => [
                    [
                        'instance' => [
                            '_type' => 'tree',
                            'actions' => [
                                [
                                    'instance' => [
                                        '_type' => 'assign_value',
                                        'parameters' => ['from' => 'name', 'to' => 'contact.name', '_pass' => true]
                                    ],
                                    'breakOnFailure' => true,
                                ],
                            ]
                        ],
                        'breakOnFailure' => true,
                    ],
                    [
                        'instance' => [
                            '_type' => 'tree',
                            'actions' => [
                                [
                                    'instance' => [
                                        '_type' => 'assign_value',
                                        'parameters' => ['from' => 'date', 'to' => 'contact.date', '_pass' => true]
                                    ],
                                    'breakOnFailure' => false,
                                ],
                            ]
                        ],
                        'breakOnFailure' => true,
                    ],
                ],
            ],
            'condition configuration' => [
                'source' => [
                    [
                        '@tree' => [
                            'conditions' => ['@not_empty' => '$contact'],
                            'actions' => [
                                [
                                    '@assign_value' => [
                                        'conditions' => ['@not_empty' => '$contact.foo'],
                                        'parameters' => ['from' => 'name', 'to' => 'contact.foo'],
                                    ]
                                ],
                            ],
                            'break_on_failure' => false,
                        ]
                    ],
                ],
                'expected' => [
                    [
                        'instance' => [
                            '_type' => 'tree',
                            'actions' => [
                                [
                                    'instance' => [
                                        '_type' => 'assign_value',
                                        'parameters' => ['from' => 'name', 'to' => 'contact.foo', '_pass' => true],
                                        'condition' => ['_type' => 'configurable', '@not_empty' => '$contact.foo'],
                                    ],
                                    'breakOnFailure' => true,
                                ],
                            ],
                            'condition' => [
                                '_type' => 'configurable',
                                '@not_empty' => '$contact'
                            ],
                        ],
                        'breakOnFailure' => false,
                    ],
                ],
            ],
        ];
    }

    public function addPostAction(TreeExecutor $treeExecutor, ActionInterface $action, bool $breakOnFailure)
    {
        $actionData = [];
        if ($action instanceof TreeExecutor) {
            $actionData = [
                '_type'   => TreeExecutor::ALIAS,
                'actions' => $this->getActions($action),
            ];
        } elseif ($action instanceof ArrayAction) {
            $actionData = $action->toArray();
        }

        $conditionData = $this->getCondition($action);
        if ($conditionData) {
            $actionData['condition'] = $conditionData;
        }

        $treeActions = $this->getActions($treeExecutor);
        $treeActions[] = [
            'instance'       => $actionData,
            'breakOnFailure' => $breakOnFailure
        ];

        ReflectionUtil::setPropertyValue($treeExecutor, 'actions', $treeActions);
    }

    private function getActions(TreeExecutor $action): array
    {
        return ReflectionUtil::getPropertyValue($action, 'actions');
    }

    public function getTreeExecutor(): TreeExecutor
    {
        $treeExecutor = $this->getMockBuilder(TreeExecutor::class)
            ->onlyMethods(['addAction'])
            ->getMock();
        $treeExecutor->expects($this->any())
            ->method('addAction')
            ->willReturnCallback(function ($action, $breakOnFailure) use ($treeExecutor) {
                /** @var TreeExecutor $treeExecutor */
                $this->addPostAction($treeExecutor, $action, $breakOnFailure);
            });

        return $treeExecutor;
    }

    private function getCondition(ActionInterface $postAction): ?array
    {
        /** @var ArrayCondition $condition */
        $condition = null;
        if ($postAction instanceof TreeExecutor) {
            $condition = ReflectionUtil::getPropertyValue($postAction, 'condition');
        } elseif ($postAction instanceof ArrayAction) {
            $condition = $postAction->getCondition();
        }

        return $condition ? $condition->toArray() : null;
    }
}
