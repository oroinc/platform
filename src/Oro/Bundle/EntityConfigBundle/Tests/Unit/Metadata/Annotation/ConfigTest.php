<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Annotation;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider constructorDataProvider
     */
    public function testConstructor(
        $data,
        $expectedMode,
        $expectedRouteName,
        $expectedRouteView,
        $expectedDefaultValues
    ) {
        $config = new Config($data);
        $this->assertEquals($expectedMode, $config->mode);
        $this->assertEquals($expectedRouteName, $config->routeName);
        $this->assertEquals($expectedRouteView, $config->routeView);
        $this->assertEquals($expectedDefaultValues, $config->defaultValues);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\AnnotationException
     */
    public function testIncorrectMode()
    {
        new Config(['mode' => 'some mode']);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\AnnotationException
     */
    public function testIncorrectDefaultValues()
    {
        new Config(['defaultValues' => 'some string']);
    }

    public function constructorDataProvider()
    {
        return [
            [
                [],
                'default',
                '',
                '',
                [],
            ],
            [
                ['mode' => 'readonly'],
                'readonly',
                '',
                '',
                [],
            ],
            [
                ['value' => 'readonly'],
                'readonly',
                '',
                '',
                [],
            ],
            [
                [
                    'mode'          => 'readonly',
                    'routeName'     => 'test_route_name',
                    'routeView'     => 'test_route_view',
                    'defaultValues' => [
                        'test' => 'test_val'
                    ]
                ],
                'readonly',
                'test_route_name',
                'test_route_view',
                [
                    'test' => 'test_val'
                ],
            ],
        ];
    }
}
