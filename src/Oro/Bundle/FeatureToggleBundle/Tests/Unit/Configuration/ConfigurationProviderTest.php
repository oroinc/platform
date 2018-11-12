<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;
use Oro\Bundle\FeatureToggleBundle\Exception\CircularReferenceException;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;

class ConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param array $bundles
     * @param array $mergedConfiguration
     */
    public function testWarmUpCache(array $configuration, array $bundles, array $mergedConfiguration)
    {
        $config = new FeatureToggleConfiguration();
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $config,
            $cache
        );

        $cache->expects($this->once())
            ->method('save')
            ->with(FeatureToggleConfiguration::ROOT, $mergedConfiguration);

        $configurationProvider->warmUpCache();
    }

    public function testClearCache()
    {
        $config = new FeatureToggleConfiguration();
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            [],
            [],
            $config,
            $cache
        );

        $cache->expects($this->once())
            ->method('delete')
            ->with(FeatureToggleConfiguration::ROOT);
        $configurationProvider->clearCache();
    }

    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param array $bundles
     * @param array $mergedConfiguration
     */
    public function testGetFeaturesConfigurationFromCache(
        array $configuration,
        array $bundles,
        array $mergedConfiguration
    ) {
        $config = new FeatureToggleConfiguration();
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $config,
            $cache
        );

        $ignoreCache = false;
        $cache->expects($this->once())
            ->method('fetch')
            ->with(FeatureToggleConfiguration::ROOT)
            ->willReturn($mergedConfiguration);
        $this->assertEquals(
            $mergedConfiguration[ConfigurationProvider::FEATURES],
            $configurationProvider->getFeaturesConfiguration($ignoreCache)
        );
    }

    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param array $bundles
     * @param array $mergedConfiguration
     */
    public function testGetResourcesConfigurationIgnoreCache(
        array $configuration,
        array $bundles,
        array $mergedConfiguration
    ) {
        $config = new FeatureToggleConfiguration();
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $config,
            $cache
        );

        $ignoreCache = true;
        $cache->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            $mergedConfiguration[ConfigurationProvider::INTERNAL][ConfigurationProvider::BY_RESOURCE],
            $configurationProvider->getResourcesConfiguration($ignoreCache)
        );
    }

    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param array $bundles
     * @param array $mergedConfiguration
     */
    public function testGetDependentsConfigurationNotInCache(
        array $configuration,
        array $bundles,
        array $mergedConfiguration
    ) {
        $config = new FeatureToggleConfiguration();
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $config,
            $cache
        );

        $ignoreCache = false;
        $cache->expects($this->once())
            ->method('fetch')
            ->with(FeatureToggleConfiguration::ROOT)
            ->willReturn(false);
        $cache->expects($this->once())
            ->method('save')
            ->with(FeatureToggleConfiguration::ROOT, $mergedConfiguration);

        $this->assertEquals(
            $mergedConfiguration[ConfigurationProvider::INTERNAL][ConfigurationProvider::DEPENDENT_FEATURES],
            $configurationProvider->getDependentsConfiguration($ignoreCache)
        );
    }

    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param array $bundles
     * @param array $mergedConfiguration
     */
    public function testGetDependentsConfigurationInCache(
        array $configuration,
        array $bundles,
        array $mergedConfiguration
    ) {
        $config = new FeatureToggleConfiguration();
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $config,
            $cache
        );

        $ignoreCache = false;
        $cache->expects($this->once())
            ->method('fetch')
            ->with(FeatureToggleConfiguration::ROOT)
            ->willReturn($mergedConfiguration);

        $this->assertEquals(
            $mergedConfiguration[ConfigurationProvider::INTERNAL][ConfigurationProvider::DEPENDENT_FEATURES],
            $configurationProvider->getDependentsConfiguration($ignoreCache)
        );
    }

    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param array $bundles
     * @param array $mergedConfiguration
     */
    public function testGetDependenciesConfigurationNotInCache(
        array $configuration,
        array $bundles,
        array $mergedConfiguration
    ) {
        $config = new FeatureToggleConfiguration();
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $config,
            $cache
        );

        $ignoreCache = false;
        $cache->expects($this->once())
            ->method('fetch')
            ->with(FeatureToggleConfiguration::ROOT)
            ->willReturn(false);
        $cache->expects($this->once())
            ->method('save')
            ->with(FeatureToggleConfiguration::ROOT, $mergedConfiguration);

        $this->assertEquals(
            $mergedConfiguration[ConfigurationProvider::INTERNAL][ConfigurationProvider::DEPENDENCIES],
            $configurationProvider->getDependenciesConfiguration($ignoreCache)
        );
    }

    public function testGetDependenciesConfigurationCircularReferenceTwoLevel()
    {
        $configuration = [
            TestBundle1::class => [
                'feature1' => [
                    'label' => 'Feature 1',
                    'toggle' => 'toggle1',
                    'dependencies' => ['feature2'],
                ],
                'feature2' => [
                    'label' => 'Feature 2',
                    'toggle' => 'toggle2',
                    'dependencies' => ['feature3'],
                ],
                'feature3' => [
                    'label' => 'Feature 3',
                    'toggle' => 'toggle3',
                    'dependencies' => ['feature1'],
                ],
            ],
        ];
        $bundles = [TestBundle1::class];

        $this->expectException(CircularReferenceException::class);

        $ignoreCache = true;
        $config = new FeatureToggleConfiguration();
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $config,
            $cache
        );
        $configurationProvider->getDependenciesConfiguration($ignoreCache);
    }

    public function testGetDependenciesConfigurationCircularReferenceOneLevel()
    {
        $configuration = [
            TestBundle1::class => [
                'feature1' => [
                    'label' => 'Feature 1',
                    'toggle' => 'toggle1',
                    'dependencies' => ['feature2'],
                ],
                'feature2' => [
                    'label' => 'Feature 2',
                    'toggle' => 'toggle2',
                    'dependencies' => ['feature1'],
                ],
            ],
        ];
        $bundles = [TestBundle1::class];

        $this->expectException(CircularReferenceException::class);

        $ignoreCache = true;
        $config = new FeatureToggleConfiguration();
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $config,
            $cache
        );
        $configurationProvider->getDependenciesConfiguration($ignoreCache);
    }

    /**
     * @return array
     */
    public function configurationDataProvider()
    {
        return [
            [
                'configuration' => [
                    TestBundle1::class => [
                        'feature1' => [
                            'label' => 'Feature 1',
                            'toggle' => 'toggle1',
                            'description' => 'Description 1',
                            'dependencies' => ['feature2'],
                            'routes' => ['f1_route1', 'f1_route2'],
                            'configuration' => ['config_section1', 'config_leaf1']
                        ],
                    ],
                    TestBundle2::class => [
                        'feature1' => [
                            'toggle' => 'changed_toggle',
                            'routes' => ['f1_route3'],
                            'configuration' => ['config_leaf2']
                        ],
                        'feature2' => [
                            'label' => 'Feature 2',
                            'toggle' => 'toggle2',
                            'routes' => ['f1_route3'],
                            'configuration' => ['config_leaf2']
                        ],
                        'feature3' => [
                            'label' => 'Feature 3',
                            'toggle' => 'toggle3',
                            'dependencies' => ['feature1'],
                        ],
                    ],
                ],
                'bundles' => [TestBundle1::class, TestBundle2::class],
                'mergedConfiguration' => [
                    ConfigurationProvider::FEATURES => [
                        'feature1' => [
                            'label' => 'Feature 1',
                            'toggle' => 'changed_toggle',
                            'description' => 'Description 1',
                            'dependencies' => ['feature2'],
                            'routes' => ['f1_route1', 'f1_route2', 'f1_route3'],
                            'configuration' => ['config_section1', 'config_leaf1', 'config_leaf2'],
                            'entities' => [],
                            'field_configs' => [],
                            'commands' => []
                        ],
                        'feature2' => [
                            'label' => 'Feature 2',
                            'toggle' => 'toggle2',
                            'dependencies' => [],
                            'routes' => ['f1_route3'],
                            'configuration' => ['config_leaf2'],
                            'entities' => [],
                            'field_configs' => [],
                            'commands' => []
                        ],
                        'feature3' => [
                            'label' => 'Feature 3',
                            'toggle' => 'toggle3',
                            'dependencies' => ['feature1'],
                            'routes' => [],
                            'configuration' => [],
                            'entities' => [],
                            'field_configs' => [],
                            'commands' => []
                        ],
                    ],
                    ConfigurationProvider::INTERNAL => [
                        ConfigurationProvider::BY_RESOURCE => [
                            'routes' => [
                                'f1_route1' => ['feature1'],
                                'f1_route2' => ['feature1'],
                                'f1_route3' => ['feature1', 'feature2'],
                            ],
                            'configuration' => [
                                'config_section1' => ['feature1'],
                                'config_leaf1' => ['feature1'],
                                'config_leaf2' => ['feature1', 'feature2'],
                            ]
                        ],
                        ConfigurationProvider::DEPENDENCIES => [
                            'feature1' => ['feature2'],
                            'feature2' => [],
                            'feature3' => ['feature1', 'feature2'],
                        ],
                        ConfigurationProvider::DEPENDENT_FEATURES => [
                            'feature1' => ['feature3'],
                            'feature2' => ['feature1', 'feature3'],
                            'feature3' => [],
                        ],
                    ],
                ],
            ],
        ];
    }
}
