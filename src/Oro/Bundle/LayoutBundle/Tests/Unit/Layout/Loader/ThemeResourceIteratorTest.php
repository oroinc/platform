<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader;

use Oro\Bundle\LayoutBundle\Layout\Loader\ThemeResourceIterator;

class ThemeResourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIteratorShouldPassResourcesWithCorrectPaths()
    {
        $resources = [
            'default.yml',
            'oro_dashboard_view' => [
                'default.yml',
                'update.php'
            ],
            'oro_window'         => [
                '3rd_level' => [
                    'default.yml',
                ]
            ]
        ];

        $created = [];
        $factory = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactoryInterface');
        $factory->expects($this->any())->method('create')
            ->willReturnCallback(
                function ($path, $resource) use (&$created) {
                    return $created[] = [$path, $resource];
                }
            );

        $iterator = new ThemeResourceIterator($factory, $resources);
        iterator_to_array($iterator);

        $this->assertSame(
            [
                ['0', 'default.yml'],
                ['oro_dashboard_view/0', 'default.yml'],
                ['oro_dashboard_view/1', 'update.php'],
                ['oro_window/3rd_level/0', 'default.yml'],
            ],
            $created
        );
    }
}
