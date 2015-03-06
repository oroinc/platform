<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader;

use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceIterator;

class ResourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    protected $resources = [
        'base'  => [
            'default.yml',
            'oro_dashboard_view' => [
                'default2.yml',
                'update.php'
            ],
            'oro_window'         => [
                '3rd_level' => [
                    'default3.yml',
                ]
            ]
        ],
        'black' => [
            'default_black.yml',
            'oro_dashboard_view' => [
                'default_black.php'
            ],
        ]
    ];

    public function testIteratorReturnAllKnownResources()
    {
        $this->assertSame(
            [
                'default.yml',
                'default2.yml',
                'update.php',
                'default3.yml',
                'default_black.yml',
                'default_black.php'
            ],
            $this->getCreatedResources()
        );
    }

    public function testIteratorReturnResourcesForTheme()
    {
        $this->assertSame(
            [
                'default_black.yml',
            ],
            $this->getCreatedResources('black')
        );
    }

    public function testIteratorReturnResourcesForRouteInTheme()
    {
        $this->assertSame(
            [
                'default2.yml',
                'update.php',
            ],
            $this->getCreatedResources('base/oro_dashboard_view')
        );
    }

    /**
     * @param null|string $path
     *
     * @return array
     */
    protected function getCreatedResources($path = null)
    {
        $created = [];
        $factory = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactoryInterface');
        $factory->expects($this->any())->method('create')
            ->willReturnCallback(
                function ($path, $resource) use (&$created) {
                    return $created[] = $resource;
                }
            );

        $iterator = new ResourceIterator($factory, $this->resources);
        if (null !== $path) {
            $pathArray = explode('/', $path);

            $matcher = $this->getMockBuilder('Oro\Bundle\LayoutBundle\Layout\Loader\ResourceMatcher')
                ->disableOriginalConstructor()->getMock();
            $matcher->expects($this->any())->method('match')
                ->willReturnCallback(
                    function (array $currentPath) use ($pathArray) {
                        return $currentPath === $pathArray;
                    }
                );

            $iterator->setMatcher($matcher);
        }
        iterator_to_array($iterator);

        return $created;
    }
}
