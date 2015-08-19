<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Form;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

class FormActionTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateEmpty()
    {
        $formAction = FormAction::createEmpty();

        $this->assertTrue($formAction->isEmpty());

        $this->assertFalse(isset($formAction[FormAction::PATH]));
        $this->assertFalse(isset($formAction[FormAction::ROUTE_NAME]));
        $this->assertFalse(isset($formAction[FormAction::ROUTE_PARAMETERS]));

        $this->assertNull($formAction[FormAction::PATH]);
        $this->assertNull($formAction[FormAction::ROUTE_NAME]);
        $this->assertNull($formAction[FormAction::ROUTE_PARAMETERS]);

        $this->assertNull($formAction->getPath());
        $this->assertNull($formAction->getRouteName());
        $this->assertNull($formAction->getRouteParameters());

        $this->assertSame('', $formAction->toString());
    }

    public function testCreateByPath()
    {
        $path = 'test';

        $formAction = FormAction::createByPath($path);

        $this->assertFalse($formAction->isEmpty());

        $this->assertTrue(isset($formAction[FormAction::PATH]));
        $this->assertFalse(isset($formAction[FormAction::ROUTE_NAME]));
        $this->assertFalse(isset($formAction[FormAction::ROUTE_PARAMETERS]));

        $this->assertEquals($path, $formAction[FormAction::PATH]);
        $this->assertNull($formAction[FormAction::ROUTE_NAME]);
        $this->assertNull($formAction[FormAction::ROUTE_PARAMETERS]);

        $this->assertEquals($path, $formAction->getPath());
        $this->assertNull($formAction->getRouteName());
        $this->assertNull($formAction->getRouteParameters());

        $this->assertSame('path:' . $path, $formAction->toString());
    }

    public function testCreateByRouteWithoutParameters()
    {
        $routeName = 'test';

        $formAction = FormAction::createByRoute($routeName);

        $this->assertFalse($formAction->isEmpty());

        $this->assertFalse(isset($formAction[FormAction::PATH]));
        $this->assertTrue(isset($formAction[FormAction::ROUTE_NAME]));
        $this->assertTrue(isset($formAction[FormAction::ROUTE_PARAMETERS]));

        $this->assertNull($formAction[FormAction::PATH]);
        $this->assertEquals($routeName, $formAction[FormAction::ROUTE_NAME]);
        $this->assertEquals([], $formAction[FormAction::ROUTE_PARAMETERS]);

        $this->assertNull($formAction->getPath());
        $this->assertEquals($routeName, $formAction->getRouteName());
        $this->assertEquals([], $formAction->getRouteParameters());

        $this->assertSame('route:' . $routeName, $formAction->toString());
    }

    public function testCreateByRouteWithParameters()
    {
        $routeName   = 'test';
        $routeParams = ['foo' => 'bar'];

        $formAction = FormAction::createByRoute($routeName, $routeParams);

        $this->assertFalse($formAction->isEmpty());

        $this->assertFalse(isset($formAction[FormAction::PATH]));
        $this->assertTrue(isset($formAction[FormAction::ROUTE_NAME]));
        $this->assertTrue(isset($formAction[FormAction::ROUTE_PARAMETERS]));

        $this->assertNull($formAction[FormAction::PATH]);
        $this->assertEquals($routeName, $formAction[FormAction::ROUTE_NAME]);
        $this->assertEquals($routeParams, $formAction[FormAction::ROUTE_PARAMETERS]);

        $this->assertNull($formAction->getPath());
        $this->assertEquals($routeName, $formAction->getRouteName());
        $this->assertEquals($routeParams, $formAction->getRouteParameters());

        $this->assertSame('route:' . $routeName, $formAction->toString());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The path must be a string, but "integer" given.
     */
    public function testCreateByPathShouldAcceptStringOnly()
    {
        FormAction::createByPath(123);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The path must not be empty.
     */
    public function testCreateByPathShouldNotAcceptEmptyString()
    {
        FormAction::createByPath('');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The path must be a string, but "NULL" given.
     */
    public function testCreateByPathShouldNotAcceptNull()
    {
        FormAction::createByPath(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The route name must be a string, but "integer" given.
     */
    public function testCreateByRouteShouldAcceptStringOnly()
    {
        FormAction::createByRoute(123);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The route name must not be empty.
     */
    public function testCreateByRouteShouldNotAcceptEmptyString()
    {
        FormAction::createByRoute('');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The route name must be a string, but "NULL" given.
     */
    public function testCreateByRouteShouldNotAcceptNull()
    {
        FormAction::createByRoute(null);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testArrayAccessSetDenied()
    {
        $formAction = FormAction::createByPath('test');

        $formAction[FormAction::PATH] = 'new';
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testArrayAccessUnsetDenied()
    {
        $formAction = FormAction::createByPath('test');

        unset($formAction[FormAction::PATH]);
    }
}
