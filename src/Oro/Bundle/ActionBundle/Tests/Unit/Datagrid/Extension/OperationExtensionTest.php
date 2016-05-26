<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Extension\OperationExtension;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;

class OperationExtensionTest extends AbstractExtensionTest
{
    const PROVIDER_ALIAS = 'test_mass_action_provider';
    const TEST_ROUTE = 'test_route';

    /** @var \PHPUnit_Framework_MockObject_MockObject|MassActionProviderRegistry */
    protected $massActionProviderRegistry;

    /** @var OperationExtension */
    protected $extension;

    protected function setUp()
    {
        parent::setUp();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper $contextHelper */
        $contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $contextHelper->expects($this->any())
            ->method('getActionData')
            ->willReturn(new ActionData(['data' => ['param'], 'key1' => 'value1', 'key2' => 2]));
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

        $provider = $this->getMock('Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface');
        $provider->expects($this->any())
            ->method('getActions')
            ->willReturn(['test_config' => ['label' => 'test_label']]);

        $this->massActionProviderRegistry = $this
            ->getMockBuilder('Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->massActionProviderRegistry->expects($this->any())
            ->method('getProvider')
            ->with(self::PROVIDER_ALIAS)
            ->willReturn($provider);

        /* @var $optionsHelper OptionsHelper|\PHPUnit_Framework_MockObject_MockObject */
        $optionsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\OptionsHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $optionsHelper->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn(['options' => ['option1' => 'value1', 'option2' => 'value2']]);

        $this->extension = new OperationExtension(
            $this->manager,
            $contextHelper,
            $this->massActionProviderRegistry,
            $optionsHelper,
            $this->gridConfigurationHelper
        );
    }

    protected function tearDown()
    {
        unset($this->extension, $this->massActionProviderRegistry);

        parent::tearDown();
    }

    /**
     * @param DatagridConfiguration $config
     * @param Operation[] $operations
     * @param bool $expected
     * @param array $expectedConfiguration
     *
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable(
        DatagridConfiguration $config,
        array $operations,
        $expected,
        array $expectedConfiguration = []
    ) {
        $this->manager->expects($this->once())
            ->method('getOperations')
            ->willReturn($operations);

        $this->assertEquals($expected, $this->extension->isApplicable($config));

        if ($expected) {
            $options = $config->offsetGetOr('options');

            $this->assertInternalType('array', $options);
            $this->assertArrayHasKey('urlParams', $options);
            $this->assertArrayHasKey('originalRoute', $options['urlParams']);
            $this->assertEquals(self::TEST_ROUTE, $options['urlParams']['originalRoute']);

            $this->assertNotEmpty($config->offsetGetOr('actions'));
            $this->assertNotEmpty($config->offsetGetOr('action_configuration'));

            foreach ($expectedConfiguration as $name => $params) {
                $this->assertNotEmpty($config->offsetGetOr($name));
                $this->assertEquals($params, $config->offsetGetOr($name));
            }
        }
    }

    /**
     * @param DatagridConfiguration $datagridConfig
     * @param ResultRecord $record
     * @param array $actions
     * @param array $expectedActions
     * @param array $context
     * @param array $groups
     *
     * @dataProvider getRowConfigurationProvider
     */
    public function testGetRowConfiguration(
        DatagridConfiguration $datagridConfig,
        ResultRecord $record,
        $actions,
        array $expectedActions,
        array $context = null,
        array $groups = null
    ) {
        $this->manager->expects($this->any())
            ->method('getOperations')
            ->with($context, false)
            ->willReturn($actions);

        if ($groups) {
            $this->extension->setGroups($groups);
        }

        if ($this->extension->isApplicable($datagridConfig)) {
            $actionConfigurationCallback = $datagridConfig->offsetGet(ActionExtension::ACTION_CONFIGURATION_KEY);

            $this->assertInstanceOf('Closure', $actionConfigurationCallback);

            $this->assertEquals($expectedActions, call_user_func($actionConfigurationCallback, $record, []));
        }
    }

    /**
     * @return array
     */
    public function isApplicableProvider()
    {
        return [
            'applicable with provider' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid1']),
                'actions' => ['test_operation' => $this->createOperation(
                    'test_operation',
                    true,
                    [
                        'getDatagridOptions' => ['mass_action_provider' => self::PROVIDER_ALIAS]
                    ]
                )],
                'expected' => true,
                'expectedConfiguration' => [
                    'mass_actions' => ['test_operationtest_config' => ['label' => 'test_label']]
                ]
            ],
            'applicable with single mass action' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid1']),
                'actions' => ['test_operation' => $this->createOperation(
                    'test_operation',
                    true,
                    [
                        'getDatagridOptions' => ['mass_action' => ['label' => 'test_mass_action_label']]
                    ]
                )],
                'expected' => true,
                'expectedConfiguration' => [
                    'mass_actions' => ['test_operation' => ['label' => 'test_mass_action_label']]
                ]
            ],
            'applicable with single action' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid1']),
                'actions' => ['action3' => $this->createOperation(
                    'action3',
                    true,
                    [
                        'getName' => 'action3',
                        'getLabel' => 'Action 3 label'
                    ]
                )],
                'expected' => true,
                'expectedConfiguration' => [
                    'actions' => ['action3' => $this->getRowActionConfig('action3', 'Action 3 label')],
                ]
            ],
            'should not replace existing default action' => [
                'config' => DatagridConfiguration::create(['actions' => ['action3' => ['label' => 'default action3']]]),
                'actions' => ['action3' => $this->createOperation(
                    'action3',
                    true,
                    [
                        'getName' => 'action3',
                        'getLabel' => 'Action 3 label'
                    ]
                ), 'test_operation' => $this->createOperation(
                    'test_operation',
                    true,
                    [
                        'getDatagridOptions' => ['mass_action' => ['label' => 'test_mass_action_label']]
                    ]
                )],
                'expected' => true,
                'expectedConfiguration' => [
                    'actions' => [
                        'action3' => ['label' => 'default action3'],
                        'test_operation' => $this->getRowActionConfig('test_operation'),
                    ]
                ]
            ],
            'not applicable' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid1']),
                'actions' => [],
                'expected' => false
            ]
        ];
    }

    /**
     * @return array
     */
    public function getRowConfigurationProvider()
    {
        return [
            'no actions' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid_name']),
                'record' => new ResultRecord(['id' => 1]),
                'actions' => [],
                'expectedActions' => [],
                'context' => ['entityClass' => null, 'datagrid' => 'datagrid_name', 'group' => null],
                'groups' => null,
            ],
            'no actions and group1' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid_name']),
                'record' => new ResultRecord(['id' => 1]),
                'actions' => [],
                'expectedActions' => [],
                'context' => ['entityClass' => null, 'datagrid' => 'datagrid_name', 'group' => ['group1']],
                'groups' => ['group1'],
            ],
            '2 allowed actions' => [
                'config' => DatagridConfiguration::create([]),
                'record' => new ResultRecord(['id' => 2]),
                'actions' => [
                    'action1' => $this->createOperation('operation1', true),
                    'action2' => $this->createOperation('operation2', true)
                ],
                'expectedActions' => [
                    'action1' => ['option1' => 'value1', 'option2' => 'value2'],
                    'action2' => ['option1' => 'value1', 'option2' => 'value2'],
                ],
                'context' => ['entityClass' => null, 'datagrid' => null, 'group' => null],
            ],
            '1 allowed action' => [
                'config' => DatagridConfiguration::create([]),
                'record' => new ResultRecord(['id' => 3]),
                'actions' => [
                    'action1' => $this->createOperation('operation1', true),
                    'action3' => $this->createOperation('operation3', false)
                ],
                'expectedActions' => [
                    'action1' => ['option1' => 'value1', 'option2' => 'value2'],
                    'action3' => false
                ],
                'context' => ['entityClass' => null, 'datagrid' => null, 'group' => null],
            ],
            '1 allowed action and array parent config' => [
                'config' => DatagridConfiguration::create([
                    'name' => 'datagrid_name',
                    ActionExtension::ACTION_CONFIGURATION_KEY => [
                        'view' => ['key1' => 'value1'],
                        'update' => false,
                    ],
                ]),
                'record' => new ResultRecord(['id' => 4]),
                'actions' => [
                    'action1' => $this->createOperation('operation1', true),
                    'action3' => $this->createOperation('operation3', false)
                ],
                'expectedActions' => [
                    'action1' => ['option1' => 'value1', 'option2' => 'value2'],
                    'action3' => false,
                    'view' => ['key1' => 'value1'],
                    'update' => false,
                ],
                'context' => ['entityClass' => null, 'datagrid' => 'datagrid_name', 'group' => null],
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
                ]),
                'record' => new ResultRecord(['id' => 4]),
                'actions' => [
                    'action1' => $this->createOperation('operation1', true),
                    'action3' => $this->createOperation('operation3', false)
                ],
                'expectedActions' => [
                    'action1' => ['option1' => 'value1', 'option2' => 'value2'],
                    'action3' => false,
                    'view' => ['key2' => 'value2'],
                    'update' => true
                ],
                'context' => ['entityClass' => null, 'datagrid' => 'datagrid_name', 'group' => null],
            ],
        ];
    }

    /**
     * @param string $action
     * @param string $label
     * @return array
     */
    protected function getRowActionConfig($action, $label = null)
    {
        return [
            'type' => 'action-widget',
            'label' => $label,
            'rowAction' => false,
            'link' => '#',
            'icon' => 'edit',
            'options' => [
                'operationName' => $action,
            ]
        ];
    }
}
