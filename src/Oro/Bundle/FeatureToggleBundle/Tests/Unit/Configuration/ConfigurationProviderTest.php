<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtension;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle3\TestBundle3;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle4\TestBundle4;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle5\TestBundle5;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class ConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private const CONFIGURATION = [
        '__features__' => [
            'feature1' => [
                'label'         => 'feature1.label',
                'description'   => 'feature1.description',
                'toggle'        => 'changed_toggle',
                'dependencies'  => ['feature2'],
                'routes'        => ['f1_route1', 'f1_route2', 'f1_route3'],
                'configuration' => ['config_section1', 'config_leaf1', 'config_leaf2'],
                'entities'      => [],
                'commands'      => [],
                'mq_topics'     => []
            ],
            'feature2' => [
                'label'         => 'feature2.label',
                'toggle'        => 'toggle2',
                'dependencies'  => [],
                'routes'        => ['f1_route3'],
                'configuration' => ['config_leaf2'],
                'entities'      => [],
                'commands'      => [],
                'mq_topics'     => []
            ],
            'feature3' => [
                'label'         => 'feature3.label',
                'dependencies'  => ['feature1'],
                'routes'        => [],
                'configuration' => [],
                'entities'      => [],
                'commands'      => [],
                'mq_topics'     => []
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
            ],
            'toggles'            => [
                'changed_toggle' => 'feature1',
                'toggle2'        => 'feature2'
            ]
        ]
    ];

    /** @var ConfigurationExtension|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationExtension;

    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = $this->getTempFile('FeatureToggleConfigurationProvider');
        $this->configurationExtension = $this->createMock(ConfigurationExtension::class);
    }

    private function getConfigurationProvider(
        array $bundleClasses,
        bool $skipConfigurationExtensionExpectations = false
    ): ConfigurationProvider {
        $bundles = [];
        foreach ($bundleClasses as $bundleClass) {
            /** @var BundleInterface $bundle */
            $bundle = new $bundleClass();
            $bundles[$bundle->getName()] = $bundleClass;
        }
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($bundles);

        if (!$skipConfigurationExtensionExpectations) {
            $this->configurationExtension->expects(self::once())
                ->method('processConfiguration')
                ->willReturnArgument(0);
        }

        return new ConfigurationProvider(
            $this->cacheFile,
            false,
            new FeatureToggleConfiguration($this->configurationExtension),
            $this->configurationExtension
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
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The feature "feature1" has circular reference on itself.');

        $configurationProvider = $this->getConfigurationProvider([TestBundle4::class]);
        $configurationProvider->getDependenciesConfiguration();
    }

    public function testGetDependenciesConfigurationCircularReferenceOneLevel()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The feature "feature1" has circular reference on itself.');

        $configurationProvider = $this->getConfigurationProvider([TestBundle3::class]);
        $configurationProvider->getDependenciesConfiguration();
    }

    public function testGetFeatureByToggle()
    {
        $configurationProvider = $this->getConfigurationProvider([TestBundle1::class, TestBundle2::class]);

        self::assertEquals(
            self::CONFIGURATION['__internal__']['toggles'],
            $configurationProvider->getTogglesConfiguration()
        );
    }

    public function testGetFeatureByToggleWhenSameToggleUsedForSeveralFeatures()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'A toggle can be used for one feature only, but the toggle "toggle1" is used for two features,'
            . ' "feature1" and "feature2".'
        );

        $configurationProvider = $this->getConfigurationProvider([TestBundle1::class, TestBundle5::class]);
        $configurationProvider->getTogglesConfiguration();
    }

    public function testClearCache()
    {
        $this->configurationExtension->expects(self::once())
            ->method('clearConfigurationCache');

        $configurationProvider = $this->getConfigurationProvider([], true);

        // guard
        $configurationProvider->getFeaturesConfiguration();
        self::assertFileExists($this->cacheFile);

        $configurationProvider->clearCache();
        self::assertFileDoesNotExist($this->cacheFile);
    }
}
