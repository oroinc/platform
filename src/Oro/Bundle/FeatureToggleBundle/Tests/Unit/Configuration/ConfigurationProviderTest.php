<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle3\TestBundle3;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle4\TestBundle4;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class ConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private const CONFIGURATION = [
        '__features__' => [
            'feature1' => [
                'label'         => 'Feature 1',
                'toggle'        => 'changed_toggle',
                'description'   => 'Description 1',
                'dependencies'  => ['feature2'],
                'routes'        => ['f1_route1', 'f1_route2', 'f1_route3'],
                'configuration' => ['config_section1', 'config_leaf1', 'config_leaf2'],
                'entities'      => [],
                'field_configs' => [],
                'commands'      => [],
                'sidebar_widgets' => [],
                'dashboard_widgets' => [],
                'cron_jobs' => [],
                'api_resources' => [],
                'navigation_items' => [],
                'operations' => [],
                'workflows' => [],
                'processes' => [],
                'placeholder_items' => [],
                'mq_topics' => []
            ],
            'feature2' => [
                'label'         => 'Feature 2',
                'toggle'        => 'toggle2',
                'dependencies'  => [],
                'routes'        => ['f1_route3'],
                'configuration' => ['config_leaf2'],
                'entities'      => [],
                'field_configs' => [],
                'commands'      => [],
                'sidebar_widgets' => [],
                'dashboard_widgets' => [],
                'cron_jobs' => [],
                'api_resources' => [],
                'navigation_items' => [],
                'operations' => [],
                'workflows' => [],
                'processes' => [],
                'placeholder_items' => [],
                'mq_topics' => []
            ],
            'feature3' => [
                'label'         => 'Feature 3',
                'toggle'        => 'toggle3',
                'dependencies'  => ['feature1'],
                'routes'        => [],
                'configuration' => [],
                'entities'      => [],
                'field_configs' => [],
                'commands'      => [],
                'sidebar_widgets' => [],
                'dashboard_widgets' => [],
                'cron_jobs' => [],
                'api_resources' => [],
                'navigation_items' => [],
                'operations' => [],
                'workflows' => [],
                'processes' => [],
                'placeholder_items' => [],
                'mq_topics' => []
            ]
        ],
        '__internal__' => [
            'by_resource'        => [
                'routes'        => [
                    'f1_route1' => ['feature1'],
                    'f1_route2' => ['feature1'],
                    'f1_route3' => ['feature1', 'feature2']
                ],
                'configuration' => [
                    'config_section1' => ['feature1'],
                    'config_leaf1'    => ['feature1'],
                    'config_leaf2'    => ['feature1', 'feature2']
                ]
            ],
            'dependencies'       => [
                'feature1' => ['feature2'],
                'feature2' => [],
                'feature3' => ['feature1', 'feature2']
            ],
            'dependent_features' => [
                'feature1' => ['feature3'],
                'feature2' => ['feature1', 'feature3'],
                'feature3' => []
            ]
        ]
    ];

    /** @var string */
    private $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = $this->getTempFile('FeatureToggleConfigurationProvider');
    }

    /**
     * @param string[] $bundleClasses
     *
     * @return ConfigurationProvider
     */
    private function getConfigurationProvider(array $bundleClasses)
    {
        $bundles = [];
        foreach ($bundleClasses as $bundleClass) {
            /** @var BundleInterface $bundle */
            $bundle = new $bundleClass();
            $bundles[$bundle->getName()] = $bundleClass;
        }
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($bundles);

        return new ConfigurationProvider(
            $this->cacheFile,
            false,
            $bundles,
            new FeatureToggleConfiguration()
        );
    }

    public function testGetFeaturesConfiguration()
    {
        $configurationProvider = $this->getConfigurationProvider([TestBundle1::class, TestBundle2::class]);

        self::assertEquals(
            self::CONFIGURATION['__features__'],
            $configurationProvider->getFeaturesConfiguration()
        );
    }

    public function testGetResourcesConfiguration()
    {
        $configurationProvider = $this->getConfigurationProvider([TestBundle1::class, TestBundle2::class]);

        self::assertEquals(
            self::CONFIGURATION['__internal__']['by_resource'],
            $configurationProvider->getResourcesConfiguration()
        );
    }

    public function testGetDependenciesConfiguration()
    {
        $configurationProvider = $this->getConfigurationProvider([TestBundle1::class, TestBundle2::class]);

        self::assertEquals(
            self::CONFIGURATION['__internal__']['dependencies'],
            $configurationProvider->getDependenciesConfiguration()
        );
    }

    public function testGetDependentsConfiguration()
    {
        $configurationProvider = $this->getConfigurationProvider([TestBundle1::class, TestBundle2::class]);

        self::assertEquals(
            self::CONFIGURATION['__internal__']['dependent_features'],
            $configurationProvider->getDependentsConfiguration()
        );
    }

    public function testGetDependenciesConfigurationCircularReferenceTwoLevel()
    {
        $this->expectException(\Oro\Bundle\FeatureToggleBundle\Exception\CircularReferenceException::class);
        $this->expectExceptionMessage('Feature "feature1" has circular reference on itself');

        $configurationProvider = $this->getConfigurationProvider([TestBundle4::class]);
        $configurationProvider->getDependenciesConfiguration();
    }

    public function testGetDependenciesConfigurationCircularReferenceOneLevel()
    {
        $this->expectException(\Oro\Bundle\FeatureToggleBundle\Exception\CircularReferenceException::class);
        $this->expectExceptionMessage('Feature "feature1" has circular reference on itself');

        $configurationProvider = $this->getConfigurationProvider([TestBundle3::class]);
        $configurationProvider->getDependenciesConfiguration();
    }
}
