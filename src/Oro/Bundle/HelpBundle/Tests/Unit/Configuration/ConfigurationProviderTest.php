<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\Configuration;

use Oro\Bundle\HelpBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\HelpBundle\Tests\Unit\Fixtures\Bundles\BarBundle\BarBundle;
use Oro\Bundle\HelpBundle\Tests\Unit\Fixtures\Bundles\FooBundle\FooBundle;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class ConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private ConfigurationProvider $configurationProvider;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('HelpConfigurationProvider');

        $this->configurationProvider = new ConfigurationProvider($cacheFile, false);
    }

    public function testGetConfiguration()
    {
        $bundle1 = new BarBundle();
        $bundle2 = new FooBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        self::assertEquals(
            [
                'vendors'   => [
                    'Bar' => [
                        'alias' => 'BarAliasOverride'
                    ],
                    'Foo' => [
                        'alias' => 'FooAlias'
                    ]
                ],
                'resources' => [
                    'Bar\Bundle\BarBundle\Controller\BarController'     => [
                        'alias' => 'Foo\Bundle\FooBundle\Controller\FooController'
                    ],
                    'Bar\Bundle\BarBundle\Controller\BarController::fooAction' => [
                        'server' => 'http://server.com/',
                        'prefix' => 'baz/prefix',
                        'alias'  => 'bar/alias',
                        'uri'    => 'bar/uri',
                        'link'   => 'http://server.com/foo/custom'
                    ]
                ],
                'routes'    => [
                    'bar_route' => [
                        'server' => 'http://server.com/',
                        'uri'    => 'bar/override',
                        'link'   => 'http://server.com/bar/custom'
                    ],
                    'foo_route' => [
                        'server' => 'http://server.com/',
                        'uri'    => 'foo/uri',
                        'link'   => 'http://server.com/foo'
                    ]
                ]
            ],
            $this->configurationProvider->getConfiguration()
        );
    }
}
