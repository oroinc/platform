<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Oro\Bundle\WorkflowBundle\Datagrid\ActionPermissionProvider;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var ActionPermissionProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface')
            ->getMock();
        $this->provider = new ActionPermissionProvider($this->configProvider);
    }

    /**
     * @param array $expected
     * @param object $input
     * @dataProvider getWorkflowDefinitionPermissionsDataProvider
     */
    public function testGetWorkflowDefinitionPermissionsSystemRelated(array $expected, $input)
    {
        $this->assertEquals($expected, $this->provider->getWorkflowDefinitionPermissions($input));
    }

    public function getWorkflowDefinitionPermissionsDataProvider()
    {
        $systemDefinition = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');
        $systemDefinition->expects($this->any())
            ->method('getValue')
            ->will($this->returnValueMap(array(array('system', true))));

        $regularDefinition = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');
        $regularDefinition->expects($this->any())
            ->method('getValue')
            ->will($this->returnValueMap(array(array('system', false))));

        return array(
            'system definition' => array(
                'expected' => array(
                    'update' => false,
                    'clone'  => true,
                    'delete' => false,
                    'activate' => true,
                    'deactivate' => false
                ),
                'input' => $systemDefinition
            ),
            'regular definition' => array(
                'expected' => array(
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ),
                'input' => $regularDefinition
            )
        );
    }

    /**
     * @param array $expected
     * @param object $input
     * @param bool $hasConfig
     * @param string $activeWorkflowName
     * @dataProvider getWorkflowDefinitionActivationDataProvider
     */
    public function testGetWorkflowDefinitionPermissionsActivationRelated(
        array $expected,
        $input,
        $hasConfig,
        $activeWorkflowName
    ) {
        $relatedEntity = $input->getValue('entityClass');
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($relatedEntity)
            ->will($this->returnValue($hasConfig));
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

    public function getWorkflowDefinitionActivationDataProvider()
    {
        $definition = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');
        $definition->expects($this->any())
            ->method('getValue')
            ->will(
                $this->returnValueMap(
                    array(
                        array('name', 'workflow_name'),
                        array('entityClass', '\stdClass')
                    )
                )
            );

        return array(
            'no config' => array(
                'expected' => array(
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ),
                'input' => $definition,
                false,
                null
            ),
            'active definition' => array(
                'expected' => array(
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => true
                ),
                'input' => $definition,
                true,
                'workflow_name'
            ),
            'inactive definition' => array(
                'expected' => array(
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ),
                'input' => $definition,
                true,
                'other_workflow_name'
            )
        );
    }
}
