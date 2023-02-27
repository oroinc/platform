<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Provider;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Button\OperationButton;
use Oro\Bundle\ActionBundle\Datagrid\Provider\DatagridActionButtonProvider;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Tests\Unit\Stub\StubButton;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class DatagridActionButtonProviderTest extends \PHPUnit\Framework\TestCase
{
    private const PROVIDER_ALIAS = 'test_mass_action_provider';
    private const TEST_ROUTE = 'test_route';

    /** @var ButtonProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $buttonProvider;

    /** @var DatagridActionButtonProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->buttonProvider = $this->createMock(ButtonProvider::class);

        $contextHelper = $this->createMock(ContextHelper::class);
        $contextHelper->expects($this->any())
            ->method('getContext')
            ->willReturn(
                [
                    ContextHelper::ROUTE_PARAM => self::TEST_ROUTE,
                    ContextHelper::ENTITY_ID_PARAM => null,
                    ContextHelper::ENTITY_CLASS_PARAM => null,
                    ContextHelper::DATAGRID_PARAM => null,
                    ContextHelper::GROUP_PARAM => null,
                    ContextHelper::FROM_URL_PARAM => null,
                ]
            );

        $massActionProvider = $this->createMock(MassActionProviderInterface::class);
        $massActionProvider->expects($this->any())
            ->method('getActions')
            ->willReturn(['test_config' => ['label' => 'test_label', 'translatable' => false]]);

        $massActionProviderRegistry = $this->createMock(MassActionProviderRegistry::class);
        $massActionProviderRegistry->expects($this->any())
            ->method('getProvider')
            ->with(self::PROVIDER_ALIAS)
            ->willReturn($massActionProvider);

        $optionsHelper = $this->createMock(OptionsHelper::class);
        $optionsHelper->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn([
                'options' => [
                    'option1' => 'value1',
                    'option2' => 'value2',
                ],
                'data' => [
                    'key1' => 'value1',
                ],
            ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return sprintf('[trans]%s[/trans]', $key);
            });

        $this->provider = new DatagridActionButtonProvider(
            $this->buttonProvider,
            $contextHelper,
            $massActionProviderRegistry,
            $optionsHelper,
            $this->createMock(EntityClassResolver::class),
            $translator
        );
    }

    /**
     * @dataProvider onConfigureActionsProvider
     */
    public function testApplyActions(
        DatagridConfiguration $config,
        ButtonsCollection $buttonCollection,
        bool $expected,
        array $expectedConfiguration = []
    ): void {
        $this->buttonProvider->expects($this->once())
            ->method('match')
            ->willReturn($buttonCollection);

        $this->provider->hasActions($config);
        if ($expected) {
            $this->provider->applyActions($config);
            $options = $config->offsetGetOr('options');

            $this->assertIsArray($options);
            $this->assertArrayHasKey('urlParams', $options);
            $this->assertArrayHasKey('originalRoute', $options['urlParams']);
            $this->assertEquals(self::TEST_ROUTE, $options['urlParams']['originalRoute']);

            $this->assertNotEmpty($config->offsetGetOr('actions'));
            $this->assertNotEmpty($config->offsetGetOr('action_configuration'));

            foreach ($expectedConfiguration as $name => $params) {
                $this->assertNotEmpty($config->offsetGetOr($name));
                $this->assertEquals($params, $config->offsetGetOr($name));
            }
        } else {
            $this->assertEmpty($config->offsetGetOr('options'));
            $this->assertEmpty($config->offsetGetOr('actions'));
            $this->assertEmpty($config->offsetGetOr('action_configuration'));
        }
    }

    /**
     * @dataProvider getRowConfigurationProvider
     */
    public function testGetRowConfiguration(
        DatagridConfiguration $datagridConfig,
        ResultRecord $record,
        ButtonsCollection $buttonsCollection,
        array $expectedActions,
        array $groups = null
    ): void {
        $this->buttonProvider->expects($this->any())
            ->method('match')
            ->willReturn($buttonsCollection);

        if ($groups) {
            $this->provider->setGroups($groups);
        }

        $this->provider->applyActions($datagridConfig);

        $actionConfigurationCallback = $datagridConfig->offsetGetOr(ActionExtension::ACTION_CONFIGURATION_KEY, []);

        if ($actionConfigurationCallback) {
            $this->assertInstanceOf('Closure', $actionConfigurationCallback);

            $this->assertEquals($expectedActions, $actionConfigurationCallback($record, []));
        } else {
            $this->assertEmpty($actionConfigurationCallback);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onConfigureActionsProvider(): array
    {
        return [
            'configure with provider' => [
                'config' => DatagridConfiguration::create([
                    'name' => 'datagrid1',
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                    ],
                ]),
                'buttonCollection' => $this->createButtonsCollection(
                    [
                        $this->createOperationButton(
                            'test_operation',
                            true,
                            ['mass_action_provider' => self::PROVIDER_ALIAS]
                        )
                    ]
                ),
                'expected' => true,
                'expectedConfiguration' => [
                    'mass_actions' => [
                        'test_operationtest_config' => ['label' => 'test_label', 'translatable' => false]
                    ]
                ]
            ],
            'configure with single mass action' => [
                'config' => DatagridConfiguration::create([
                    'name' => 'datagrid1',
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                    ],
                ]),
                'buttonCollection' => $this->createButtonsCollection(
                    [
                        $this->createOperationButton(
                            'test_operation',
                            true,
                            ['mass_action' => ['label' => 'test_mass_action_label', 'icon' => 'test_icon']]
                        )
                    ]
                ),
                'expected' => true,
                'expectedConfiguration' => [
                    'mass_actions' => [
                        'test_operation' => ['label' => 'test_mass_action_label', 'icon' => 'test_icon']
                    ]
                ]
            ],
            'configure with single action' => [
                'config' => DatagridConfiguration::create([
                    'name' => 'datagrid1',
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                    ],
                ]),
                'buttonCollection' => $this->createButtonsCollection(
                    [
                        $this->createOperationButton(
                            'action3',
                            true,
                            ['data' => ['key1' => 'value1'], 'aria_label' => 'test_aria_label'],
                            'Action 3 label'
                        )
                    ]
                ),
                'expected' => true,
                'expectedConfiguration' => [
                    'actions' => [
                        'action3' => $this->getRowActionConfig(
                            '[trans]Action 3 label[/trans]',
                            ['key1' => 'value1']
                        )
                    ],
                ]
            ],
            'should not replace existing default action' => [
                'config' => DatagridConfiguration::create([
                    'name' => 'datagrid1',
                    'actions' => [
                        'action3' => ['label' => 'default action3', 'translatable' => false]
                    ],
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                    ],
                ]),
                'buttonCollection' => $this->createButtonsCollection(
                    [
                        $this->createButton(
                            'action3',
                            true,
                            [
                                'getLabel' => 'Action 3 label'
                            ]
                        ),
                        $this->createOperationButton(
                            'test_operation',
                            true,
                            [
                                'label' => 'test_mass_action_label',
                                'translatable' => false,
                                'aria_label' => 'test_aria_label'
                            ]
                        )
                    ]
                ),
                'expected' => true,
                'expectedConfiguration' => [
                    'actions' => [
                        'action3' => ['label' => 'default action3', 'translatable' => false],
                        'test_operation' => $this->getRowActionConfig('[trans][/trans]'),
                    ]
                ]
            ],
            'not configure' => [
                'config' => DatagridConfiguration::create([
                    'name' => 'datagrid1',
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                    ],
                ]),
                'buttonCollection' => $this->createButtonsCollection([]),
                'expected' => false
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getRowConfigurationProvider(): array
    {
        return [
            'no actions' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid_name']),
                'record' => new ResultRecord(['id' => 1]),
                'buttonCollection' => $this->createButtonsCollection([]),
                'expectedActions' => [],
                'groups' => null,
            ],
            'no actions and group1' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid_name']),
                'record' => new ResultRecord(['id' => 1]),
                'buttonCollection' => $this->createButtonsCollection([]),
                'expectedActions' => [],
                'groups' => ['group1'],
            ],
            '2 allowed actions' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid_name']),
                'record' => new ResultRecord(['id' => 2]),
                'buttonCollection' => $this->createButtonsCollection(
                    [
                        $this->createButton('operation1', true),
                        $this->createButton('operation2', true)
                    ]
                ),
                'expectedActions' => [
                    'operation1' => ['option1' => 'value1', 'option2' => 'value2', 'key1' => 'value1'],
                    'operation2' => ['option1' => 'value1', 'option2' => 'value2', 'key1' => 'value1'],
                ],
            ],
            '1 allowed action' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid_name']),
                'record' => new ResultRecord(['id' => 3]),
                'buttonCollection' => $this->createButtonsCollection(
                    [
                        $this->createButton('operation1', true),
                        $this->createButton('operation3', false)
                    ]
                ),
                'expectedActions' => [
                    'operation1' => ['option1' => 'value1', 'option2' => 'value2', 'key1' => 'value1'],
                    'operation3' => false
                ],
            ],
            '1 allowed action and array parent config' => [
                'config' => DatagridConfiguration::create([
                    'name' => 'datagrid_name',
                    ActionExtension::ACTION_CONFIGURATION_KEY => [
                        'view' => ['key1' => 'value1'],
                        'update' => false,
                    ],
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                    ],
                ]),
                'record' => new ResultRecord(['id' => 4]),
                'buttonCollection' => $this->createButtonsCollection(
                    [
                        $this->createButton('action1', true, ['order' => 1]),
                        $this->createButton('action3', false, ['order' => 2])
                    ]
                ),
                'expectedActions' => [
                    'action1' => ['option1' => 'value1', 'option2' => 'value2', 'key1' => 'value1'],
                    'action3' => false,
                    'view' => ['key1' => 'value1'],
                    'update' => false,
                ],
            ],
            'operations not allowed' => [
                'config' => DatagridConfiguration::create([
                    'name' => 'datagrid_name',
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                    ],
                ]),
                'record' => new ResultRecord([]),
                'buttonCollection' => $this->createButtonsCollection(
                    [
                        $this->createButton('operation1', true),
                        $this->createButton('operation2', false)
                    ]
                ),
                'expectedActions' => [
                    'operation1' => false,
                    'operation2' => false
                ]
            ],
            '1 allowed action and callable parent config' => [
                'config' => DatagridConfiguration::create([
                    'name' => 'datagrid_name',
                    ActionExtension::ACTION_CONFIGURATION_KEY => function () {
                        return [
                            'view' => ['key2' => 'value2'],
                            'update' => true,
                        ];
                    },
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                    ],
                ]),
                'record' => new ResultRecord(['id' => 4]),
                'buttonCollection' => $this->createButtonsCollection(
                    [
                        $this->createButton('action1', true, ['order' => 1]),
                        $this->createButton('action3', false, ['order' => 2])
                    ]
                ),
                'expectedActions' => [
                    'action1' => ['option1' => 'value1', 'option2' => 'value2', 'key1' => 'value1'],
                    'action3' => false,
                    'view' => ['key2' => 'value2'],
                    'update' => true
                ],
            ]
        ];
    }

    public function testOnConfigureActionsWithFewDatagrids(): void
    {
        $config1 = DatagridConfiguration::create([
            'name' => 'datagrid1',
            ActionExtension::ACTION_CONFIGURATION_KEY => function () {
                return [
                    'action1' => ['actionKey1' => 'actionValue1'],
                ];
            },
            'source' => [
                'type' => OrmDatasource::TYPE,
            ],
        ]);

        $buttonCollection1 = $this->createButtonsCollection(
            [
                'operation1' => $this->createOperationButton('operation1', true),
                'operation2' => $this->createOperationButton('operation2', true),
            ]
        );

        $config2 = DatagridConfiguration::create([
            'name' => 'datagrid2',
            ActionExtension::ACTION_CONFIGURATION_KEY => function () {
                return [
                    'action2' => ['actionKey2' => 'actionValue2'],
                ];
            },
            'source' => [
                'type' => OrmDatasource::TYPE,
            ],
        ]);

        $buttonCollection2 = $this->createButtonsCollection(
            [
                'opearation3' => $this->createOperationButton('operation3', true),
            ]
        );

        $this->buttonProvider->expects($this->exactly(2))
            ->method('match')
            ->willReturnOnConsecutiveCalls($buttonCollection1, $buttonCollection2);

        $this->provider->applyActions($config1);
        $this->provider->applyActions($config2);

        $callback1 = $config1->offsetGet('action_configuration');
        $this->assertEquals(
            [
                'action1' => [
                    'actionKey1' => 'actionValue1',
                ],
                'operation1' => [
                    'option1' => 'value1',
                    'option2' => 'value2',
                    'key1' => 'value1',
                ],
                'operation2' => [
                    'option1' => 'value1',
                    'option2' => 'value2',
                    'key1' => 'value1',
                ],
            ],
            $callback1(new ResultRecord(['id' => 1]), [])
        );

        $callback2 = $config2->offsetGet('action_configuration');
        $this->assertEquals(
            [
                'action2' => [
                    'actionKey2' => 'actionValue2',
                ],
                'operation3' => [
                    'option1' => 'value1',
                    'option2' => 'value2',
                    'key1' => 'value1',
                ],
            ],
            $callback2(new ResultRecord(['id' => 1]), [])
        );
    }

    private function getRowActionConfig(string $label = null, array $data = []): array
    {
        return array_merge([
            'type' => 'button-widget',
            'label' => $label,
            'translatable' => false,
            'ariaLabel' => 'test_aria_label',
            'rowAction' => false,
            'link' => '#',
            'icon' => 'pencil-square-o',
        ], $data);
    }

    private function createButton(string $name, bool $isAvailable, array $extraData = []): ButtonInterface
    {
        $buttonContext = new ButtonContext();
        $buttonContext->setEnabled($isAvailable);

        return new StubButton(
            array_merge(
                [
                    'name' => $name,
                    'templateData' => ['additionalData' => []],
                    'buttonContext' => $buttonContext
                ],
                $extraData
            )
        );
    }

    private function createOperationButton(
        string $name,
        bool $isAvailable,
        array $datagridOptions = [],
        string $label = null
    ): ButtonInterface {
        $definition = $this->createMock(OperationDefinition::class);
        $definition->expects($this->any())
            ->method('getLabel')
            ->willReturn($label);
        $definition->expects($this->any())
            ->method('getDatagridOptions')
            ->willReturn($datagridOptions);

        $operation = $this->createMock(Operation::class);
        $operation->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        $buttonContext = new ButtonContext();
        $buttonContext->setEnabled($isAvailable);

        return new OperationButton($name, $operation, $buttonContext, new ActionData());
    }

    private function createButtonsCollection(array $buttons): ButtonsCollection
    {
        $extension = $this->createMock(ButtonProviderExtensionInterface::class);
        $extension->expects($this->once())
            ->method('find')
            ->willReturn($buttons);
        $extension->expects($this->any())
            ->method('isAvailable')
            ->willReturnCallback(function (ButtonInterface $button) {
                return $button->getButtonContext()->isEnabled();
            });

        $collection = new ButtonsCollection();
        $collection->consume($extension, new ButtonSearchContext());

        return $collection;
    }
}
