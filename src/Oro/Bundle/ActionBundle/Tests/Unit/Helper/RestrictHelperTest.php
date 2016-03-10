<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;

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
            /** @var Action|\PHPUnit_Framework_MockObject_MockObject|Action $action */
            $action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
                ->disableOriginalConstructor()
                ->getMock();
            $actionDefinition = new ActionDefinition();
            $actionDefinition->setButtonOptions($buttonOptions);
            $action->expects($this->any())->method('getDefinition')->willReturn($actionDefinition);
            $actions[$actionName] = $action;
        }
        /** @var Action[] $actions */
        $restrictedActions = $this->helper->restrictActionsByGroup($actions, $definedGroups);
        foreach ($expectedActions as $expectedActionName) {
            $this->assertArrayHasKey($expectedActionName, $actions);
            $this->assertArrayHasKey($expectedActionName, $restrictedActions);
            $this->assertEquals(
                spl_object_hash($actions[$expectedActionName]),
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
