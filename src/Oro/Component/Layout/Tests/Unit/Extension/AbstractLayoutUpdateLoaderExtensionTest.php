<?php

namespace Oro\Tests\Unit\Component\Layout\Extension;

use Oro\Component\Layout\Tests\Unit\Fixtures\AbstractLayoutUpdateLoaderExtensionStub;

class AbstractLayoutUpdateLoaderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    protected static $resources = [
        'oro-default' => [
            'resource1.yml',
            'resource2.xml',
            'resource3.php'
        ],
        'oro-gold' => [
            'resource-gold.yml',
            'index' => [
                'resource-update.yml'
            ]
        ]
    ];

    /**
     * @dataProvider findApplicableResourcesDataProvider
     */
    public function testFindApplicableResources($expected, $paths)
    {
        $extension = $this->getAbstractLayoutUpdateLoaderExtension();
        $this->assertEquals($expected, $extension->findApplicableResources($paths));
    }

    public function findApplicableResourcesDataProvider()
    {
        return [
            [
                [
                    'resource1.yml',
                    'resource2.xml',
                    'resource3.php'
                ],
                [
                    "base",
                    "base/page",
                    "oro-default"
                ]
            ],
            [
                [],
                [
                    "base",
                    "base/page"
                ]
            ]
        ];
    }

    /**
     * @return AbstractLayoutUpdateLoaderExtensionStub
     */
    protected function getAbstractLayoutUpdateLoaderExtension()
    {
        return new AbstractLayoutUpdateLoaderExtensionStub(self::$resources);
    }
}
