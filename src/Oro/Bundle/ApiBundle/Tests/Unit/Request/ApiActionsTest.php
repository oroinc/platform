<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ApiActions;

class ApiActionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider isInputActionProvider
     */
    public function testIsInputAction($action, $result)
    {
        $this->assertEquals($result, ApiActions::isInputAction($action));
    }

    public function isInputActionProvider()
    {
        return [
            ['get', false],
            ['get_list', false],
            ['update', true],
            ['create', true],
            ['delete', false],
            ['delete_list', false],
            ['get_subresource', false],
            ['get_relationship', false],
            ['update_relationship', true],
            ['add_relationship', true],
            ['delete_relationship', false],
        ];
    }

    /**
     * @dataProvider isOutputActionProvider
     */
    public function testIsOutputAction($action, $result)
    {
        $this->assertEquals($result, ApiActions::isOutputAction($action));
    }

    public function isOutputActionProvider()
    {
        return [
            ['get', true],
            ['get_list', true],
            ['update', true],
            ['create', true],
            ['delete', false],
            ['delete_list', false],
            ['get_subresource', true],
            ['get_relationship', true],
            ['update_relationship', true],
            ['add_relationship', true],
            ['delete_relationship', false],
        ];
    }

    /**
     * @dataProvider isIdentificatorNeededForActionProvider
     */
    public function testIsIdentificatorNeededForAction($action, $result)
    {
        $this->assertEquals($result, ApiActions::isIdentificatorNeededForAction($action));
    }

    public function isIdentificatorNeededForActionProvider()
    {
        return [
            ['get', true],
            ['get_list', true],
            ['update', true],
            ['create', false],
            ['delete', true],
            ['delete_list', true],
            ['get_subresource', true],
            ['get_relationship', true],
            ['update_relationship', true],
            ['add_relationship', true],
            ['delete_relationship', true],
        ];
    }

    /**
     * @dataProvider getActionOutputFormatActionTypeProvider
     */
    public function testGetActionOutputFormatActionType($action, $result)
    {
        $this->assertEquals($result, ApiActions::getActionOutputFormatActionType($action));
    }

    public function getActionOutputFormatActionTypeProvider()
    {
        return [
            ['get', 'get'],
            ['get_list', 'get_list'],
            ['update', 'get'],
            ['create', 'get'],
            ['delete', 'delete'],
            ['delete_list', 'delete_list'],
            ['get_subresource', 'get_subresource'],
            ['get_relationship', 'get_relationship'],
            ['update_relationship', 'update_relationship'],
            ['add_relationship', 'add_relationship'],
            ['delete_relationship', 'delete_relationship'],
        ];
    }
}
