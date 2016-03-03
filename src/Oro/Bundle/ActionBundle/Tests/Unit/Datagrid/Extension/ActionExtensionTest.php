<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Extension\ActionExtension;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

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
            ->willReturn(new ActionData(['data' => ['param']]));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ApplicationsHelper $applicationHelper */
        $applicationHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper')
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->extension = new ActionExtension(
            $this->manager,
            $contextHelper,
            $applicationHelper,
            $this->massActionProviderRegistry
        );
    }

    protected function tearDown()
    {
        unset($this->extension, $this->manager, $this->massActionProviderRegistry);
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
            ->method('getActions')
            ->willReturn($operations);

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
     * @param ResultRecord $record
     * @param array $actions
     * @param array $expectedActions
     *
     * @dataProvider getActionsPermissionsProvider
     */
    public function testGetActionsPermissions(ResultRecord $record, array $actions, array $expectedActions)
    {
        $this->manager->expects($this->any())
            ->method('getActions')
            ->willReturn($actions);

        $this->extension->isApplicable(DatagridConfiguration::create(['name' => 'datagrid_name']));

        $this->assertEquals($expectedActions, $this->extension->getActionsPermissions($record));
    }

    /**
     * @return array
     */
    public function isApplicableProvider()
    {
        $operation1 = $this->createOperation(
            'test_operation',
            true,
            [
                'getDatagridOptions' => ['mass_action_provider' => self::PROVIDER_ALIAS]
            ]
        );

        $operation2 = $this->createOperation(
            'test_operation',
            true,
            [
                'getDatagridOptions' => ['mass_action' => ['label' => 'test_mass_action_label']]
            ]
        );

        return [
            'applicable with provider' => [
                'config' => DatagridConfiguration::create(['name' => ' datagrid1']),
                'actions' => [$operation1],
                'expected' => true,
                'expectedConfiguration' => [
                    'mass_actions' => ['test_operationtest_config' => ['label' => 'test_label']]
                ]
            ],
            'applicable with single mass action' => [
                'config' => DatagridConfiguration::create(['name' => ' datagrid1']),
                'actions' => [$operation2],
                'expected' => true,
                'expectedConfiguration' => [
                    'mass_actions' => ['test_operation' => ['label' => 'test_mass_action_label']]
                ]
            ],
            'not applicable' => [
                'config' => DatagridConfiguration::create(['name' => ' datagrid1']),
                'actions' => [],
                'expected' => false
            ]
        ];
    }

    /**
     * @return array
     */
    public function getActionsPermissionsProvider()
    {
        $operationAllowed1 = $this->createOperation('operation1', true);
        $operationAllowed2 = $this->createOperation('operation2', true);
        $operationNotAllowed = $this->createOperation('operation3', false);

        return [
            'no actions' => [
                'record' => new ResultRecord(['id' => 1]),
                'actions' => [],
                'expectedActions' => [],
            ],
            '2 allowed actions' => [
                'record' => new ResultRecord(['id' => 2]),
                'actions' => [$operationAllowed1, $operationAllowed2],
                'expectedActions' => ['operation1' => true, 'operation2' => true],
            ],
            '1 allowed action' => [
                'record' => new ResultRecord(['id' => 3]),
                'actions' => [$operationAllowed1, $operationNotAllowed],
                'expectedActions' => ['operation1' => true, 'operation3' => false],
            ],
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
        /** @var \PHPUnit_Framework_MockObject_MockObject|OperationDefinition $definition */
        $definition = $this->getMock('Oro\Bundle\ActionBundle\Model\OperationDefinition');

        foreach ($definitionParams as $method => $params) {
            $definition->expects($this->any())
                ->method($method)
                ->willReturn($params);
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|Operation $operation */
        $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);
        $operation->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $operation->expects($this->any())
            ->method('isAvailable')
            ->withAnyParameters()
            ->willReturn($isAvailable);

        return $operation;
    }
}
