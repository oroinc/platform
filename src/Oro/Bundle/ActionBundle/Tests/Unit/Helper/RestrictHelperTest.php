<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;

class RestrictHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var RestrictHelper */
    protected $helper;

    public function setUp()
    {
        $this->helper = new RestrictHelper();
        parent::setUp();
    }

    /**
     * @dataProvider restrictActionsByGroupDataProvider
     * @param array $actionsValues
     * @param string|array|null|bool $definedGroups
     * @param string[] $expectedActions
     */
    public function testRestrictActionsByGroup($actionsValues, $definedGroups, $expectedActions)
    {
        foreach ($actionsValues as $actionName => $buttonOptions) {
            /** @var \PHPUnit_Framework_MockObject_MockObject|Operation $operation */
            $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
                ->disableOriginalConstructor()
                ->getMock();
            $operationDefinition = new OperationDefinition();
            $operationDefinition->setButtonOptions($buttonOptions);
            $operation->expects($this->any())->method('getDefinition')->willReturn($operationDefinition);
            $operations[$actionName] = $operation;
        }
        /** @var Operation[] $actions */
        $restrictedActions = $this->helper->restrictActionsByGroup($operations, $definedGroups);
        foreach ($expectedActions as $expectedActionName) {
            $this->assertArrayHasKey($expectedActionName, $operations);
            $this->assertArrayHasKey($expectedActionName, $restrictedActions);
            $this->assertEquals(
                spl_object_hash($operations[$expectedActionName]),
                spl_object_hash($restrictedActions[$expectedActionName])
            );
        }
        foreach ($restrictedActions as $actionName => $restrictedAction) {
            $this->assertContains($actionName, $expectedActions);
        }
    }

    /**
     * @return array
     */
    public function restrictActionsByGroupDataProvider()
    {
        return [
            'groupIsString' => [
                'actionsValues' => [
                    //actionName //button options
                    'action0' => ['group' => null],
                    'action2' => ['group' => 'group1'],
                    'action3' => ['group' => 'group2'],
                    'action4' => []
                ],
                'definedGroups' => 'group1',
                'expectedActions' => ['action2']
            ],
            'groupIsArray' => [
                'actionsValues' => [
                    'action0' => ['group' => null],
                    'action2' => ['group' => 'group1'],
                    'action3' => ['group' => 'group2'],
                    'action4' => []
                ],
                'definedGroups' => ['group1', 'group2'],
                'expectedActions' => ['action2', 'action3']
            ],
            'groupIsFalse' => [
                'actionsValues' => [
                    'action0' => ['group' => null],
                    'action2' => ['group' => 'group1'],
                    'action3' => ['group' => 'group2'],
                    'action4' => []
                ],
                'definedGroups' => false,
                'expectedActions' => ['action4']
            ],
            'groupIsNull' => [
                'actionsValues' => [
                    'action0' => ['group' => null],
                    'action1' => ['group' => 'group1'],
                    'action2' => []
                ],
                'definedGroups' => null,
                'expectedActions' => ['action0', 'action1', 'action2']
            ],
        ];
    }
}
