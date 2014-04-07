<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Configuration\ConfigurationLoader;
use Oro\Bundle\DashboardBundle\Configuration\ConfigurationManager;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\DashboardBundle\Tests\Unit\Configuration\Stub\FirstDashboardBundle\FirstDashboardBundle;
use Oro\Bundle\DashboardBundle\Tests\Unit\Configuration\Stub\SecondDashboardBundle\SecondDashboardBundle;

class ConfigurationManagerLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigurationLoader
     */
    protected $loader;

    protected function setUp()
    {
        $bundles      = [
            new FirstDashboardBundle(),
            new SecondDashboardBundle()
        ];
        $this->loader = new ConfigurationLoader($bundles);
    }

    /**
     * @dataProvider getRawDashboardConfig
     */
    public function testGetDashboardConfiguration($expectedConfig)
    {
        $config = $this->loader->getDashboardConfiguration();

        $this->assertEquals($expectedConfig, $config);
    }

    public function getRawDashboardConfig()
    {
        return [
            'stub' => [
                'expectedConfig' => [
                    'main'            => [
                        'widgets' => [
                            'quick_launchpad2' => [
                                'position' => 30
                            ],
                            'quick_launchpad'  => [
                                'position' => 10
                            ]
                        ]
                    ],
                    'quick_launchpad' => [
                        'widgets' => [
                            'quick_launchpad' => [
                                'position' => 20
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
