<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;
use Oro\Bundle\FeatureToggleBundle\Exception\CircularReferenceException;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;

class ConfigurationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param array $bundles
     * @param array $mergedConfiguration
     */
    public function testWarmUpCache(array $configuration, array $bundles, array $mergedConfiguration)
    {
        $cache = $this->getMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $cache
        );

        $cache->expects($this->once())
            ->method('delete')
            ->with(FeatureToggleConfiguration::ROOT);
        $cache->expects($this->once())
            ->method('save')
            ->with(FeatureToggleConfiguration::ROOT, $mergedConfiguration);

        $configurationProvider->warmUpCache();
    }

    public function testClearCache()
    {
        $cache = $this->getMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            [],
            [],
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
        $cache = $this->getMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $cache
        );

        $ignoreCache = false;
        $cache->expects($this->once())
            ->method('contains')
            ->with(FeatureToggleConfiguration::ROOT)
            ->willReturn(true);
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
        $cache = $this->getMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
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
    public function testGetDependenciesConfigurationNotInCache(
        array $configuration,
        array $bundles,
        array $mergedConfiguration
    ) {
        $cache = $this->getMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $cache
        );

        $ignoreCache = false;
        $cache->expects($this->once())
            ->method('contains')
            ->with(FeatureToggleConfiguration::ROOT)
            ->willReturn(false);
        $cache->expects($this->once())
            ->method('delete')
            ->with(FeatureToggleConfiguration::ROOT);
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
                    'dependency' => ['feature2']
                ],
                'feature2' => [
                    'label' => 'Feature 2',
                    'toggle' => 'toggle2',
                    'dependency' => ['feature3']
                ],
                'feature3' => [
                    'label' => 'Feature 3',
                    'toggle' => 'toggle3',
                    'dependency' => ['feature1']
                ]
            ]
        ];
        $bundles = [TestBundle1::class];

        $this->setExpectedException(CircularReferenceException::class);

        $ignoreCache = true;
        $cache = $this->getMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
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
                    'dependency' => ['feature2']
                ],
                'feature2' => [
                    'label' => 'Feature 2',
                    'toggle' => 'toggle2',
                    'dependency' => ['feature1']
                ]
            ]
        ];
        $bundles = [TestBundle1::class];

        $this->setExpectedException(CircularReferenceException::class);

        $ignoreCache = true;
        $cache = $this->getMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $cache
        );
        $configurationProvider->getDependenciesConfiguration($ignoreCache);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                            'dependency' => ['feature2'],
                            'route' => ['f1_route1', 'f1_route2'],
                            'workflow' => ['f1_workflow1', 'f1_workflow2'],
                            'operation' => ['f1_operation1', 'f1_operation2'],
                            'process' => ['f1_process1', 'f1_process2'],
                            'configuration' => ['config_section1', 'config_leaf1'],
                            'api' => ['Entity1', 'Entity2'],
                        ],
                    ],
                    TestBundle2::class => [
                        'feature1' => [
                            'toggle' => 'changed_toggle',
                            'route' => ['f1_route3'],
                            'workflow' => ['f1_workflow3'],
                            'operation' => ['f1_operation3'],
                            'process' => ['f1_process3'],
                            'configuration' => ['config_leaf2'],
                            'api' => ['Entity3', 'Entity4'],
                        ],
                        'feature2' => [
                            'label' => 'Feature 2',
                            'toggle' => 'toggle2',
                            'route' => ['f1_route3'],
                            'workflow' => ['f1_workflow3'],
                            'operation' => ['f1_operation3'],
                            'process' => ['f1_process3'],
                            'configuration' => ['config_leaf2'],
                        ],
                        'feature3' => [
                            'label' => 'Feature 3',
                            'toggle' => 'toggle3',
                            'dependency' => ['feature1'],
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
                            'dependency' => ['feature2'],
                            'route' => ['f1_route1', 'f1_route2', 'f1_route3'],
                            'workflow' => ['f1_workflow1', 'f1_workflow2', 'f1_workflow3'],
                            'operation' => ['f1_operation1', 'f1_operation2', 'f1_operation3'],
                            'process' => ['f1_process1', 'f1_process2', 'f1_process3'],
                            'configuration' => ['config_section1', 'config_leaf1', 'config_leaf2'],
                            'api' => ['Entity1', 'Entity2', 'Entity3', 'Entity4'],
                        ],
                        'feature2' => [
                            'label' => 'Feature 2',
                            'toggle' => 'toggle2',
                            'dependency' => [],
                            'route' => ['f1_route3'],
                            'workflow' => ['f1_workflow3'],
                            'operation' => ['f1_operation3'],
                            'process' => ['f1_process3'],
                            'configuration' => ['config_leaf2'],
                            'api' => [],
                        ],
                        'feature3' => [
                            'label' => 'Feature 3',
                            'toggle' => 'toggle3',
                            'dependency' => ['feature1'],
                            'route' => [],
                            'workflow' => [],
                            'operation' => [],
                            'process' => [],
                            'configuration' => [],
                            'api' => [],
                        ],
                    ],
                    ConfigurationProvider::INTERNAL => [
                        ConfigurationProvider::BY_RESOURCE => [
                            'route' => [
                                'f1_route1' => ['feature1'],
                                'f1_route2' => ['feature1'],
                                'f1_route3' => ['feature1', 'feature2'],
                            ],
                            'workflow' => [
                                'f1_workflow1' => ['feature1'],
                                'f1_workflow2' => ['feature1'],
                                'f1_workflow3' => ['feature1', 'feature2'],
                            ],
                            'operation' => [
                                'f1_operation1' => ['feature1'],
                                'f1_operation2' => ['feature1'],
                                'f1_operation3' => ['feature1', 'feature2'],
                            ],
                            'process' => [
                                'f1_process1' => ['feature1'],
                                'f1_process2' => ['feature1'],
                                'f1_process3' => ['feature1', 'feature2'],
                            ],
                            'configuration' => [
                                'config_section1' => ['feature1'],
                                'config_leaf1' => ['feature1'],
                                'config_leaf2' => ['feature1', 'feature2'],
                            ],
                            'api' => [
                                'Entity1' => ['feature1'],
                                'Entity2' => ['feature1'],
                                'Entity3' => ['feature1'],
                                'Entity4' => ['feature1'],
                            ],
                        ],
                        ConfigurationProvider::DEPENDENCIES => [
                            'feature1' => ['feature2'],
                            'feature2' => [],
                            'feature3' => ['feature1', 'feature2'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
