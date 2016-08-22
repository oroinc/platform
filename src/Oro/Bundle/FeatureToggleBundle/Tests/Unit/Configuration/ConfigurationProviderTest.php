<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;
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
    public function testGetConfigurationFromCache(array $configuration, array $bundles, array $mergedConfiguration)
    {
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
        $this->assertEquals($mergedConfiguration, $configurationProvider->getConfiguration($ignoreCache));
    }

    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param array $bundles
     * @param array $mergedConfiguration
     */
    public function testGetConfigurationIgnoreCache(array $configuration, array $bundles, array $mergedConfiguration)
    {
        $cache = $this->getMock(CacheProvider::class);
        $configurationProvider = new ConfigurationProvider(
            $configuration,
            $bundles,
            $cache
        );

        $ignoreCache = true;
        $cache->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($mergedConfiguration, $configurationProvider->getConfiguration($ignoreCache));
    }

    /**
     * @dataProvider configurationDataProvider
     * @param array $configuration
     * @param array $bundles
     * @param array $mergedConfiguration
     */
    public function testGetConfigurationNotInCache(array $configuration, array $bundles, array $mergedConfiguration)
    {
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

        $this->assertEquals($mergedConfiguration, $configurationProvider->getConfiguration($ignoreCache));
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
                            'dependency' => ['feature2'],
                            'route' => ['f1_route1', 'f1_route2'],
                            'workflow' => ['f1_workflow1', 'f1_workflow2'],
                            'operation' => ['f1_operation1', 'f1_operation2'],
                            'process' => ['f1_process1', 'f1_process2'],
                            'configuration' => ['config_section1', 'config_leaf1'],
                            'api' => ['Entity1', 'Entity2'],
                        ]
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
                            'toggle' => 'toggle2'
                        ]
                    ]
                ],
                'bundles' => [TestBundle1::class, TestBundle2::class],
                'mergedConfiguration' => [
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
                        'route' => [],
                        'workflow' => [],
                        'operation' => [],
                        'process' => [],
                        'configuration' => [],
                        'api' => [],
                    ]
                ]
            ]
        ];
    }
}
