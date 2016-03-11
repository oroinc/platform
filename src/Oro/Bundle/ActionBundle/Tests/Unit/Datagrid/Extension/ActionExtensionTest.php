<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Extension\ActionExtension;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension as DatagridActionExtension;

class ActionExtensionTest extends \PHPUnit_Framework_TestCase
{
    const PROVIDER_ALIAS = 'test_mass_action_provider';

    /** @var \PHPUnit_Framework_MockObject_MockObject|MassActionProviderRegistry */
    protected $massActionProviderRegistry;

    /** @var ActionExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionManager */
    protected $manager;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper $contextHelper */
        $contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $contextHelper->expects($this->any())
            ->method('getActionData')
            ->willReturn(new ActionData(['data' => ['param'], 'key1' => 'value1', 'key2' => 2]));

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

        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsHelper $optionsHelper */
        $optionsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\OptionsHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $optionsHelper->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn(['option1' => 'value1', 'option2' => 'value2']);

        $this->extension = new ActionExtension(
            $this->manager,
            $contextHelper,
            $this->massActionProviderRegistry,
            $optionsHelper
        );
    }

    protected function tearDown()
    {
        unset($this->extension, $this->manager, $this->massActionProviderRegistry);
    }

    /**
     * @param DatagridConfiguration $config
     * @param Action[] $actions
     * @param bool $expected
     * @param array $expectedConfiguration
     *
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable(
        DatagridConfiguration $config,
        array $actions,
        $expected,
        array $expectedConfiguration = []
    ) {
        $this->manager->expects($this->once())
            ->method('getActions')
            ->willReturn($actions);

        $this->assertEquals($expected, $this->extension->isApplicable($config));

        if ($expected) {
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
     * @param $actions
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
            ->method('getActions')
            ->with($context, false)
            ->willReturn($actions);

        if ($groups) {
            $this->extension->setGroups($groups);
        }
        $this->extension->isApplicable($datagridConfig);

        $this->assertEquals($expectedActions, $this->extension->getRowConfiguration($record, []));
    }

    /**
     * @return array
     */
    public function isApplicableProvider()
    {
        $action1 = $this->createAction(
            'test_action',
            true,
            [
                'getDatagridOptions' => ['mass_action_provider' => self::PROVIDER_ALIAS]
            ]
        );

        $action2 = $this->createAction(
            'test_action',
            true,
            [
                'getDatagridOptions' => ['mass_action' => ['label' => 'test_mass_action_label']]
            ]
        );

        $action3 = $this->createAction(
            'action3',
            true,
            [
                'getName' => 'action3',
                'getLabel' => 'Action 3 label'
            ]
        );

        return [
            'applicable with provider' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid1']),
                'actions' => ['test_action' => $action1],
                'expected' => true,
                'expectedConfiguration' => [
                    'mass_actions' => ['test_actiontest_config' => ['label' => 'test_label']]
                ]
            ],
            'applicable with single mass action' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid1']),
                'actions' => ['test_action' => $action2],
                'expected' => true,
                'expectedConfiguration' => [
                    'mass_actions' => ['test_action' => ['label' => 'test_mass_action_label']]
                ]
            ],
            'applicable with single action' => [
                'config' => DatagridConfiguration::create(['name' => 'datagrid1']),
                'actions' => ['action3' => $action3],
                'expected' => true,
                'expectedConfiguration' => [
                    'actions' => ['action3' => $this->getRowActionConfig('action3', 'Action 3 label')],
                ]
            ],
            'should not replace existing default action' => [
                'config' => DatagridConfiguration::create(['actions' => ['action3' => ['label' => 'default action3']]]),
                'actions' => ['action3' => $action3, 'test_action' => $action2],
                'expected' => true,
                'expectedConfiguration' => [
                    'actions' => [
                        'action3' => ['label' => 'default action3'],
                        'test_action' => $this->getRowActionConfig('test_action'),
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
        $actionAllowed1 = $this->createAction('action1', true);
        $actionAllowed2 = $this->createAction('action2', true);
        $actionNotAllowed = $this->createAction('action3', false);

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
                'actions' => ['action1' => $actionAllowed1, 'action2' => $actionAllowed2],
                'expectedActions' => [
                    'action1' => ['option1' => 'value1', 'option2' => 'value2'],
                    'action2' => ['option1' => 'value1', 'option2' => 'value2'],
                ],
                'context' => ['entityClass' => null, 'datagrid' => null, 'group' => null],
            ],
            '1 allowed action' => [
                'config' => DatagridConfiguration::create([]),
                'record' => new ResultRecord(['id' => 3]),
                'actions' => ['action1' => $actionAllowed1, 'action3' => $actionNotAllowed],
                'expectedActions' => [
                    'action1' => ['option1' => 'value1', 'option2' => 'value2'],
                    'action3' => false
                ],
                'context' => ['entityClass' => null, 'datagrid' => null, 'group' => null],
            ],
            '1 allowed action and array parent config' => [
                'config' => DatagridConfiguration::create([
                    'name' => 'datagrid_name',
                    DatagridActionExtension::ACTION_CONFIGURATION_KEY => [
                        'view' => ['key1' => 'value1'],
                        'update' => false,
                    ],
                ]),
                'record' => new ResultRecord(['id' => 4]),
                'actions' => ['action1' => $actionAllowed1, 'action3' => $actionNotAllowed],
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
                    DatagridActionExtension::ACTION_CONFIGURATION_KEY => function () {
                        return [
                            'view' => ['key2' => 'value2'],
                            'update' => true,
                        ];
                    },
                ]),
                'record' => new ResultRecord(['id' => 4]),
                'actions' => ['action1' => $actionAllowed1, 'action3' => $actionNotAllowed],
                'expectedActions' => [
                    'action1' => ['option1' => 'value1', 'option2' => 'value2'],
                    'action3' => false,
                    'view' => ['key2' => 'value2'],
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
                'actionName' => $action,
            ]
        ];
    }

    /**
     * @param string $name
     * @param bool $isAvailable
     * @param array $definitionParams
     *
     * @return Action|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAction($name = 'test_action', $isAvailable = true, array $definitionParams = [])
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionDefinition $definition */
        $definition = $this->getMock('Oro\Bundle\ActionBundle\Model\ActionDefinition');

        foreach ($definitionParams as $method => $params) {
            $definition->expects($this->any())
                ->method($method)
                ->willReturn($params);
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|Action $action */
        $action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
            ->disableOriginalConstructor()
            ->getMock();
        $action->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);
        $action->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $action->expects($this->any())
            ->method('isAvailable')
            ->withAnyParameters()
            ->willReturn($isAvailable);

        return $action;
    }
}
