<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Annotation;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider constructorDataProvider
     *
     * @param array $data
     * @param string $expectedMode
     * @param string $expectedRouteName
     * @param string $expectedRouteView
     * @param string $expectedRouteCreate
     * @param array $expectedDefaultValues
     * @param array $expectedRoutes
     */
    public function testConstructor(
        array $data,
        $expectedMode,
        $expectedRouteName,
        $expectedRouteView,
        $expectedRouteCreate,
        array $expectedDefaultValues,
        array $expectedRoutes
    ) {
        $config = new Config($data);
        $this->assertEquals($expectedMode, $config->mode);
        $this->assertEquals($expectedRouteName, $config->routeName);
        $this->assertEquals($expectedRouteView, $config->routeView);
        $this->assertEquals($expectedRouteCreate, $config->routeCreate);
        $this->assertEquals($expectedDefaultValues, $config->defaultValues);
        $this->assertEquals($expectedRoutes, $config->routes);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\AnnotationException
     * @expectedExceptionMessage Annotation "Config" give invalid parameter "mode" : "some mode"
     */
    public function testIncorrectMode()
    {
        new Config(['mode' => 'some mode']);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\AnnotationException
     * @expectedExceptionMessage Annotation "Config" parameter "defaultValues" expect "array" but "string" given
     */
    public function testIncorrectDefaultValues()
    {
        new Config(['defaultValues' => 'some string']);
    }

    /**
     * @return array
     */
    public function constructorDataProvider()
    {
        return [
            [
                [],
                'default',
                '',
                '',
                '',
                [],
                []
            ],
            [
                ['mode' => 'readonly'],
                'readonly',
                '',
                '',
                '',
                [],
                []
            ],
            [
                ['value' => 'readonly'],
                'readonly',
                '',
                '',
                '',
                [],
                []
            ],
            [
                [
                    'mode'          => 'readonly',
                    'routeName'     => 'test_route_name',
                    'routeView'     => 'test_route_view',
                    'routeCreate'   => 'test_route_create',
                    'routeCustom'   => 'test_route_custom',
                    'defaultValues' => [
                        'test' => 'test_val'
                    ]
                ],
                'readonly',
                'test_route_name',
                'test_route_view',
                'test_route_create',
                [
                    'test' => 'test_val'
                ],
                [
                    'custom' => 'test_route_custom'
                ]
            ],
        ];
    }
}
