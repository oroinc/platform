<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Attribute;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Exception\AttributeException;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorDefaultValues()
    {
        $expectedMode = ConfigModel::MODE_DEFAULT;
        $expectedRouteName = '';
        $expectedRouteView = '';
        $expectedRouteCreate = '';
        $expectedDefaultValues = [];
        $expectedRoutes = [];

        $config = new Config();
        $this->assertEquals($expectedMode, $config->mode);
        $this->assertEquals($expectedRouteName, $config->routeName);
        $this->assertEquals($expectedRouteView, $config->routeView);
        $this->assertEquals($expectedRouteCreate, $config->routeCreate);
        $this->assertEquals($expectedDefaultValues, $config->defaultValues);
        $this->assertEquals($expectedRoutes, $config->routes);
    }

    public function testConstructor()
    {
        $expectedMode = ConfigModel::MODE_READONLY;
        $expectedRouteName = 'test_route_name';
        $expectedRouteView = 'test_route_view';
        $expectedRouteCreate = 'test_route_create';
        $expectedDefaultValues = ['test' => 'test_val'];
        $expectedRoutes = [];

        $config = new Config(
            mode: ConfigModel::MODE_READONLY,
            routeName: 'test_route_name',
            routeView: 'test_route_view',
            routeCreate: 'test_route_create',
            defaultValues: ['test' => 'test_val'],
        );

        $this->assertEquals($expectedMode, $config->mode);
        $this->assertEquals($expectedRouteName, $config->routeName);
        $this->assertEquals($expectedRouteView, $config->routeView);
        $this->assertEquals($expectedRouteCreate, $config->routeCreate);
        $this->assertEquals($expectedDefaultValues, $config->defaultValues);
        $this->assertEquals($expectedRoutes, $config->routes);
    }

    public function testConstructorWithCustomRouteArgument()
    {
        $expectedMode = ConfigModel::MODE_READONLY;
        $expectedRouteName = 'test_route_name';
        $expectedRouteView = 'test_route_view';
        $expectedRouteCreate = 'test_route_create';
        $expectedDefaultValues = ['test' => 'test_val'];
        $expectedRoutes = ['custom' => 'test_route_custom'];

        $config = new Config(
            mode: ConfigModel::MODE_READONLY,
            routeName: 'test_route_name',
            routeView: 'test_route_view',
            routeCreate: 'test_route_create',
            routeCustom: 'test_route_custom',
            defaultValues: ['test' => 'test_val'],
        );

        $this->assertEquals($expectedMode, $config->mode);
        $this->assertEquals($expectedRouteName, $config->routeName);
        $this->assertEquals($expectedRouteView, $config->routeView);
        $this->assertEquals($expectedRouteCreate, $config->routeCreate);
        $this->assertEquals($expectedDefaultValues, $config->defaultValues);
        $this->assertEquals($expectedRoutes, $config->routes);
    }

    public function testConstructorWithValueValue()
    {
        $expectedMode = ConfigModel::MODE_HIDDEN;
        $expectedRouteName = '';
        $expectedRouteView = '';
        $expectedRouteCreate = '';
        $expectedDefaultValues = [];
        $expectedRoutes = [];

        $config = new Config(
            mode: ConfigModel::MODE_READONLY,
            value: ConfigModel::MODE_HIDDEN,
        );

        $this->assertEquals($expectedMode, $config->mode);
        $this->assertEquals($expectedRouteName, $config->routeName);
        $this->assertEquals($expectedRouteView, $config->routeView);
        $this->assertEquals($expectedRouteCreate, $config->routeCreate);
        $this->assertEquals($expectedDefaultValues, $config->defaultValues);
        $this->assertEquals($expectedRoutes, $config->routes);
    }

    public function testAttributeExceptionInvalidMode()
    {
        $this->expectException(AttributeException::class);
        $this->expectExceptionMessage('Attribute "Config" has an invalid value parameter "mode" : "some mode"');

        new Config(mode: 'some mode');
    }

    public function testAttributeExceptionNonSupportedArgument()
    {
        $this->expectException(AttributeException::class);
        $this->expectExceptionMessage(
            'Attribute "Config" does not support argument : "unSupportedArgument"'
        );

        new Config(unSupportedArgument: 'tst_argument_value');
    }

    /**
     * @dataProvider arraysDataProvider
     */
    public function testAttributeExceptionArrayAsArgument($data)
    {
        $this->expectException(AttributeException::class);
        $this->expectExceptionMessage(
            'Attribute "Config" does not support array as an argument. Use named arguments instead.'
        );

        new Config($data);
    }

    public function arraysDataProvider(): array
    {
        return [
            [
                []
            ],
            [
                [
                    'mode' =>ConfigModel::MODE_READONLY,
                    'routeName' => 'test_route_name',
                    'routeView' => 'test_route_view',
                ]
            ],
            [
                [
                    'defaultValues' => [
                        'test' => 'test_val'
                    ]
                ]
            ]
        ];
    }
}
