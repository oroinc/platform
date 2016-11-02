<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Datagrid\ActionPermissionProvider;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionPermissionProvider
     */
    protected $provider;

    /**
     * @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $featureChecker;

    protected function setUp()
    {
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new ActionPermissionProvider($this->featureChecker);
    }

    /**
     * @param array $expected
     * @param object $input
     * @param bool $featureEnabled
     * @dataProvider getWorkflowDefinitionPermissionsDataProvider
     */
    public function testGetWorkflowDefinitionPermissionsSystemRelated(array $expected, $input, $featureEnabled)
    {
        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn($featureEnabled);
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
                'input' => $systemDefinition,
                'featureEnabled' => true
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
                'input' => $regularDefinition,
                'featureEnabled' => true
            ),
            'system definition feature disabled' => array(
                'expected' => array(
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => false,
                    'activate' => false,
                    'deactivate' => false
                ),
                'input' => $systemDefinition,
                'featureEnabled' => false
            ),
            'regular definition feature disabled' => array(
                'expected' => array(
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ),
                'input' => $regularDefinition,
                'featureEnabled' => false
            )
        );
    }

    /**
     * @param array $expected
     * @param object $input
     * @param bool $featureEnabled
     * @dataProvider getWorkflowDefinitionActivationDataProvider
     */
    public function testGetWorkflowDefinitionPermissionsActivationRelated(
        array $expected,
        $input,
        $featureEnabled
    ) {
        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn($featureEnabled);
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
                'input' => $this->getDefinitionMock(false),
                'featureEnabled' => true
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
                'input' => $this->getDefinitionMock(true),
                'featureEnabled' => true
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
                'input' => $this->getDefinitionMock(false),
                'featureEnabled' => true
            ),
            'no config feature disabled' => array(
                'expected' => array(
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ),
                'input' => $this->getDefinitionMock(false),
                'featureEnabled' => false
            ),
            'active definition feature disabled' => array(
                'expected' => array(
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ),
                'input' => $this->getDefinitionMock(true),
                'featureEnabled' => false
            ),
            'inactive definition feature disabled' => array(
                'expected' => array(
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ),
                'input' => $this->getDefinitionMock(false),
                'featureEnabled' => false
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
