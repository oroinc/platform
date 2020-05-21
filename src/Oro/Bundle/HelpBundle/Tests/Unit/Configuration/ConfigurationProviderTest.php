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

    /** @var ConfigurationProvider */
    private $configurationProvider;

    /** @var string */
    private $cacheFile;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->cacheFile = $this->getTempFile('HelpConfigurationProvider');

        $this->configurationProvider = new ConfigurationProvider($this->cacheFile, false);
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
                    'FooBundle'         => [
                        'alias' => 'Foo'
                    ],
                    'BarBundle'         => [
                        'alias' => 'BarOverride'
                    ],
                    'BarBundle:Bar'     => [
                        'alias' => 'BarBundleBar'
                    ],
                    'BarBundle:Bar:foo' => [
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
