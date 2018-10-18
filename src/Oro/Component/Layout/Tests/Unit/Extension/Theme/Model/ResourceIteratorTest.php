<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Oro\Component\Layout\Extension\Theme\Model\ResourceIterator;

class ResourceIteratorTest extends \PHPUnit\Framework\TestCase
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
            iterator_to_array(new ResourceIterator($this->resources))
        );
    }
}
