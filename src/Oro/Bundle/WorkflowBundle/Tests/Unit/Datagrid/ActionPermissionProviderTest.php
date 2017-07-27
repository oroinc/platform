<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Configuration\Checker\ConfigurationChecker;
use Oro\Bundle\WorkflowBundle\Datagrid\ActionPermissionProvider;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var ConfigurationChecker|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationChecker;

    /** @var ActionPermissionProvider */
    protected $provider;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)->disableOriginalConstructor()->getMock();
        $this->configurationChecker = $this->getMockBuilder(ConfigurationChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ActionPermissionProvider($this->configProvider, $this->configurationChecker);
    }

    /**
     * @dataProvider getWorkflowDefinitionPermissionsDataProvider
     *
     * @param array $expected
     * @param ResultRecordInterface $input
     * @param bool $configurationClean
     */
    public function testGetWorkflowDefinitionPermissionsSystemRelated(array $expected, $input, $configurationClean)
    {
        $this->configurationChecker->expects($this->any())->method('isClean')->willReturn($configurationClean);

        $this->assertEquals($expected, $this->provider->getWorkflowDefinitionPermissions($input));
    }

    /**
     * @return array
     */
    public function getWorkflowDefinitionPermissionsDataProvider()
    {
        $systemDefinition = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');
        $systemDefinition->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['system', true],
                    ['configuration', []],
                ]
            );

        $regularDefinition = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');
        $regularDefinition->expects($this->any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['system', false],
                    ['configuration', []],
                ]
            );

        return [
            'system definition' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => false,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $systemDefinition,
                'configurationClean' => true
            ],
            'system definition not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => false,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $systemDefinition,
                'configurationClean' => false
            ],
            'regular definition' => [
                'expected' => [
                    'view' => true,
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $regularDefinition,
                'configurationClean' => true
            ],
            'regular definition not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $regularDefinition,
                'configurationClean' => false
            ]
        ];
    }

    /**
     * @param array $expected
     * @param ResultRecordInterface $input
     * @param bool $hasConfig
     * @param string $activeWorkflowName
     * @param bool $configurationClean
     * @dataProvider getWorkflowDefinitionActivationDataProvider
     */
    public function testGetWorkflowDefinitionPermissionsActivationRelated(
        array $expected,
        $input,
        $hasConfig,
        $activeWorkflowName,
        $configurationClean
    ) {
        $relatedEntity = $input->getValue('entityClass');
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($relatedEntity)
            ->will($this->returnValue($hasConfig));

        $this->configurationChecker->expects($this->any())->method('isClean')->willReturn($configurationClean);

        if ($hasConfig) {
            $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
                ->getMock();
            $config->expects($this->once())
                ->method('get')
                ->with('active_workflow')
                ->will($this->returnValue($activeWorkflowName));

            $this->configProvider->expects($this->once())
                ->method('getConfig')
                ->with($relatedEntity)
                ->will($this->returnValue($config));
        } else {
            $this->configProvider->expects($this->never())
                ->method('getConfig');
        }

        $this->assertEquals($expected, $this->provider->getWorkflowDefinitionPermissions($input));
    }

    /**
     * @return array
     */
    public function getWorkflowDefinitionActivationDataProvider()
    {
        return [
            'no config' => [
                'expected' => [
                    'view' => true,
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $this->getDefinitionMock(),
                'hasConfig' => false,
                'activeWorkflowName' => null,
                'configurationClean' => true
            ],
            'no config not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $this->getDefinitionMock(),
                'hasConfig' => false,
                'activeWorkflowName' => null,
                'configurationClean' => false
            ],
            'active definition' => [
                'expected' => [
                    'view' => true,
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => true
                ],
                'input' => $this->getDefinitionMock(),
                'hasConfig' => true,
                'activeWorkflowName' => 'workflow_name',
                'configurationClean' => true
            ],
            'active definition not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => true
                ],
                'input' => $this->getDefinitionMock(),
                'hasConfig' => true,
                'activeWorkflowName' => 'workflow_name',
                'configurationClean' => false
            ],
            'inactive definition' => [
                'expected' => [
                    'view' => true,
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $this->getDefinitionMock(),
                'hasConfig' => true,
                'activeWorkflowName' => 'other_workflow_name',
                'configurationClean' => true
            ],
            'inactive definition not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $this->getDefinitionMock(),
                'hasConfig' => true,
                'activeWorkflowName' => 'other_workflow_name',
                'configurationClean' => false
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDefinitionMock()
    {
        $definition = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');

        $definition->expects($this->any())
            ->method('getValue')
            ->will(
                $this->returnValueMap(
                    [
                        ['name', 'workflow_name'],
                        ['entityClass', '\stdClass'],
                        ['configuration', []],
                    ]
                )
            );

        return $definition;
    }
}
