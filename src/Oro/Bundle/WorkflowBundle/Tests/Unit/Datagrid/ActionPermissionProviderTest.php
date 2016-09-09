<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\WorkflowBundle\Datagrid\ActionPermissionProvider;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionPermissionProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new ActionPermissionProvider();
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
     * @dataProvider getWorkflowDefinitionActivationDataProvider
     */
    public function testGetWorkflowDefinitionPermissionsActivationRelated(
        array $expected,
        $input
    ) {

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
                'input' => $this->getDefinitionMock(false)
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
                'input' => $this->getDefinitionMock(true)
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
                'input' => $this->getDefinitionMock(false)
            )
        );
    }

    /**
     * @param bool $active weather workflow is active
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDefinitionMock($active)
    {
        $definition = $this->getMock(ResultRecordInterface::class);

        $definition->expects($this->any())
            ->method('getValue')
            ->will(
                $this->returnValueMap(
                    array(
                        array('active', $active),
                        array('name', 'workflow_name'),
                        array('entityClass', \stdClass::class)
                    )
                )
            );

        return $definition;
    }
}
