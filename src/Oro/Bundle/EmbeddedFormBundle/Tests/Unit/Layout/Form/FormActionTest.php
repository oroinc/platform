<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Form;

use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAction;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FormActionTest extends \PHPUnit\Framework\TestCase
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

    public function testCreateByPathShouldAcceptStringOnly()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The path must be a string, but "integer" given.');

        FormAction::createByPath(123);
    }

    public function testCreateByPathShouldNotAcceptEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The path must not be empty.');

        FormAction::createByPath('');
    }

    public function testCreateByPathShouldNotAcceptNull()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The path must be a string, but "NULL" given.');

        FormAction::createByPath(null);
    }

    public function testCreateByRouteShouldAcceptStringOnly()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The route name must be a string, but "integer" given.');

        FormAction::createByRoute(123);
    }

    public function testCreateByRouteShouldNotAcceptEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The route name must not be empty.');

        FormAction::createByRoute('');
    }

    public function testCreateByRouteShouldNotAcceptNull()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The route name must be a string, but "NULL" given.');

        FormAction::createByRoute(null);
    }

    public function testArrayAccessSetDenied()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not supported');

        $formAction = FormAction::createByPath('test');

        $formAction[FormAction::PATH] = 'new';
    }

    public function testArrayAccessUnsetDenied()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not supported');

        $formAction = FormAction::createByPath('test');

        unset($formAction[FormAction::PATH]);
    }
}
