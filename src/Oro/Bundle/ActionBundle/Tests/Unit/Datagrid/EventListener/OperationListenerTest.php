<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\EventListener;

use Oro\Bundle\ActionBundle\Datagrid\EventListener\OperationListener;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\Event\ConfigureActionsBefore;
use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class OperationListenerTest extends \PHPUnit_Framework_TestCase
{
    const PROVIDER_ALIAS = 'test_mass_action_provider';
    const TEST_ROUTE = 'test_route';

    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityClassResolver */
    protected $entityClassResolver;

    /** @var GridConfigurationHelper */
    protected $gridConfigurationHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|MassActionProviderRegistry */
    protected $massActionProviderRegistry;

    /** @var OperationListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->manager = $this->getMockBuilder(OperationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityClassResolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->gridConfigurationHelper = new GridConfigurationHelper($this->entityClassResolver);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper $contextHelper */
        $contextHelper = $this->getMockBuilder(ContextHelper::class)
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

        $provider = $this->getMock(MassActionProviderInterface::class);
        $provider->expects($this->any())
            ->method('getActions')
            ->willReturn(['test_config' => ['label' => 'test_label']]);

        $this->massActionProviderRegistry = $this->getMockBuilder(MassActionProviderRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->massActionProviderRegistry->expects($this->any())
            ->method('getProvider')
            ->with(self::PROVIDER_ALIAS)
            ->willReturn($provider);

        /* @var $optionsHelper OptionsHelper|\PHPUnit_Framework_MockObject_MockObject */
        $optionsHelper = $this->getMockBuilder(OptionsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $optionsHelper->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn(['options' => ['option1' => 'value1', 'option2' => 'value2']]);

        $this->listener = new OperationListener(
            $this->manager,
            $contextHelper,
            $this->massActionProviderRegistry,
            $optionsHelper,
            $this->gridConfigurationHelper
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->listener,
            $this->massActionProviderRegistry,
            $this->manager,
            $this->entityClassResolver,
            $this->gridConfigurationHelper
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @param Operation[] $operations
     * @param bool $expected
     * @param array $expectedConfiguration
     *
     * @dataProvider onConfigureActionsProvider
     */
    public function testOnConfigureActions(
        DatagridConfiguration $config,
        array $operations,
        $expected,
        array $expectedConfiguration = []
    ) {
        $this->manager->expects($this->once())
            ->method('getOperations')
            ->willReturn($operations);

        $this->listener->onConfigureActions(new ConfigureActionsBefore($config));

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
        } else {
            $this->assertEmpty($config->offsetGetOr('options'));
            $this->assertEmpty($config->offsetGetOr('actions'));
            $this->assertEmpty($config->offsetGetOr('action_configuration'));
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
            $this->listener->setGroups($groups);
        }

        $this->listener->onConfigureActions(new ConfigureActionsBefore($datagridConfig));

        $actionConfigurationCallback = $datagridConfig->offsetGetOr(ActionExtension::ACTION_CONFIGURATION_KEY, []);

        if ($actionConfigurationCallback) {
            $this->assertInstanceOf('Closure', $actionConfigurationCallback);

            $this->assertEquals($expectedActions, call_user_func($actionConfigurationCallback, $record, []));
        } else {
            $this->assertEmpty($actionConfigurationCallback);
        }
    }

    /**
     * @return array
     */
    public function onConfigureActionsProvider()
    {
        return [
            'configure with provider' => [
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
            'configure with single mass action' => [
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
            'configure with single action' => [
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
                'config' => DatagridConfiguration::create(
                    ['name' => 'datagrid1', 'actions' => ['action3' => ['label' => 'default action3']]]
                ),
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
            'not configure' => [
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
                'config' => DatagridConfiguration::create(['name' => 'datagrid_name']),
                'record' => new ResultRecord(['id' => 2]),
                'actions' => [
                    'action1' => $this->createOperation('operation1', true),
                    'action2' => $this->createOperation('operation2', true)
                ],
                'expectedActions' => [
                    'action1' => ['option1' => 'value1', 'option2' => 'value2'],
                    'action2' => ['option1' => 'value1', 'option2' => 'value2'],
                ],
                'context' => ['entityClass' => null, 'datagrid' => 'datagrid_name', 'group' => null],
            ],
            '1 allowed action' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid_name']),
                'record' => new ResultRecord(['id' => 3]),
                'actions' => [
                    'action1' => $this->createOperation('operation1', true),
                    'action3' => $this->createOperation('operation3', false)
                ],
                'expectedActions' => [
                    'action1' => ['option1' => 'value1', 'option2' => 'value2'],
                    'action3' => false
                ],
                'context' => ['entityClass' => null, 'datagrid' => 'datagrid_name', 'group' => null],
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

    /**
     * @param string $name
     * @param bool $isAvailable
     * @param array $definitionParams
     *
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOperation($name = 'test_operation', $isAvailable = true, array $definitionParams = [])
    {
        /** @var $definition OperationDefinition|\PHPUnit_Framework_MockObject_MockObject */
        $definition = $this->getMock(OperationDefinition::class);

        foreach ($definitionParams as $method => $params) {
            $definition->expects($this->any())->method($method)->willReturn($params);
        }

        /** @var $operation Operation|\PHPUnit_Framework_MockObject_MockObject */
        $operation = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $operation->expects($this->any())->method('getDefinition')->willReturn($definition);
        $operation->expects($this->any())->method('getName')->willReturn($name);
        $operation->expects($this->any())->method('isAvailable')->willReturn($isAvailable);

        return $operation;
    }
}
