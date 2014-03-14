<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Oro\Bundle\WorkflowBundle\Datagrid\ActionPermissionProvider;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $expected
     * @param object $input
     * @dataProvider getWorkflowDefinitionPermissionsDataProvider
     */
    public function testGetWorkflowDefinitionPermissions(array $expected, $input)
    {
        $provider = new ActionPermissionProvider();
        $this->assertEquals($expected, $provider->getWorkflowDefinitionPermissions($input));
    }

    public function getWorkflowDefinitionPermissionsDataProvider()
    {
        $systemDefinition = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');
        $systemDefinition->expects($this->any())->method('getValue')->with('system')->will($this->returnValue(true));

        $regularDefinition = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');
        $regularDefinition->expects($this->any())->method('getValue')->with('system')->will($this->returnValue(false));

        return array(
            'system definition' => array(
                'expected' => array(
                    'update' => false,
                    'clone'  => true,
                    'delete' => false,
                ),
                'input' => $systemDefinition
            ),
            'regular definition' => array(
                'expected' => array(
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                ),
                'input' => $regularDefinition
            )
        );
    }
}
