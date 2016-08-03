<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\WorkflowBundle\Datagrid\ActionPermissionProvider;
use Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WorkflowSystemConfigManager
     */
    protected $configManager;

    /**
     * @var ActionPermissionProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(WorkflowSystemConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new ActionPermissionProvider($this->configManager);
    }

    /**
     * @param array $expected
     * @param object $input
     * @dataProvider getWorkflowDefinitionPermissionsDataProvider
     */
    public function testGetWorkflowDefinitionPermissionsSystemRelated(array $expected, $input)
    {
        $this->configManager->expects($this->once())->method('getActiveWorkflowNamesByEntity')->willReturn([]);
        $this->assertEquals($expected, $this->provider->getWorkflowDefinitionPermissions($input));
    }

    /**
     * @return array
     */
    public function getWorkflowDefinitionPermissionsDataProvider()
    {
        $systemDefinition = $this->getMock(ResultRecordInterface::class);
        $systemDefinition->expects($this->any())
            ->method('getValue')
            ->will($this->returnValueMap(array(array('system', true))));

        $regularDefinition = $this->getMock(ResultRecordInterface::class);
        $regularDefinition->expects($this->any())
            ->method('getValue')
            ->will($this->returnValueMap(array(array('system', false))));

        return array(
            'system definition' => array(
                'expected' => array(
                    'view' => true,
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
                    'view' => true,
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
     * @param array $activeWorkflowNames
     * @dataProvider getWorkflowDefinitionActivationDataProvider
     */
    public function testGetWorkflowDefinitionPermissionsActivationRelated(
        array $expected,
        $input,
        array $activeWorkflowNames
    ) {
        $relatedEntity = $input->getValue('entityClass');
        $this->configManager->expects($this->once())
            ->method('getActiveWorkflowNamesByEntity')
            ->with($relatedEntity)
            ->willReturn($activeWorkflowNames);

        $this->assertEquals($expected, $this->provider->getWorkflowDefinitionPermissions($input));
    }

    /**
     * @return array
     */
    public function getWorkflowDefinitionActivationDataProvider()
    {

        return array(
            'no config' => array(
                'expected' => array(
                    'view' => true,
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ),
                'input' => $this->getDefinitionMock(),
                []
            ),
            'active definition' => array(
                'expected' => array(
                    'view' => true,
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => true
                ),
                'input' => $this->getDefinitionMock(),
                ['workflow_name']
            ),
            'inactive definition' => array(
                'expected' => array(
                    'view' => true,
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ),
                'input' => $this->getDefinitionMock(),
                ['other_workflow_name']
            )
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDefinitionMock()
    {
        $definition = $this->getMock(ResultRecordInterface::class);

        $definition->expects($this->any())
            ->method('getValue')
            ->will(
                $this->returnValueMap(
                    array(
                        array('name', 'workflow_name'),
                        array('entityClass', \stdClass::class)
                    )
                )
            );

        return $definition;
    }
}
