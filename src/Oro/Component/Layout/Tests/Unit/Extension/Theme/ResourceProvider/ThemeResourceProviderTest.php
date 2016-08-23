<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\ResourceProvider;

use Oro\Component\Layout\Extension\Theme\ResourceProvider\ThemeResourceProvider;

class ThemeResourceProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeResourceProvider */
    protected $provider;

    /** @var array */
    protected $resources = [
        'oro-default' => [
            'oro-default/resource1.yml',
            'resource2' => [
                'oro-default/resource2/file2.yml'
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->provider = new ThemeResourceProvider($this->resources);
    }

    public function testFindApplicableResources()
    {
        $paths = [
            'oro-default',
            'oro-default/resource2'
        ];
        
        $this->assertEquals(
            [
                'oro-default/resource1.yml',
                'oro-default/resource2/file2.yml'
            ],
            $this->provider->findApplicableResources($paths)
        );
    }
}
