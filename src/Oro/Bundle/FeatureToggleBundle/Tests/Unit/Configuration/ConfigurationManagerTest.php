<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigurationManagerTest extends TestCase
{
    private ConfigurationProvider&MockObject $configurationProvider;
    private ConfigurationManager $configurationManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(ConfigurationProvider::class);

        $this->configurationManager = new ConfigurationManager($this->configurationProvider);
    }

    public function testGet(): void
    {
        $value = 'value';

        $this->configurationProvider->expects($this->once())
            ->method('getFeaturesConfiguration')
            ->willReturn(['feature' => ['node' => $value]]);

        $this->assertSame($value, $this->configurationManager->get('feature', 'node', 'default'));
    }

    public function testGetDefault(): void
    {
        $default = 'default';

        $this->configurationProvider->expects($this->once())
            ->method('getFeaturesConfiguration')
            ->willReturn([]);

        $this->assertSame($default, $this->configurationManager->get('feature', 'node', $default));
    }

    public function testGetDefaultWhenFeatureValueIsNull(): void
    {
        $this->configurationProvider->expects($this->once())
            ->method('getFeaturesConfiguration')
            ->willReturn(['feature' => ['node' => null]]);

        $this->assertNull($this->configurationManager->get('feature', 'node', 'default'));
    }

    public function testGetFeaturesByResource(): void
    {
        $resourceType = 'testType';
        $resource = 'testResource';
        $features = ['feature1', 'feature2'];

        $this->configurationProvider->expects($this->once())
            ->method('getResourcesConfiguration')
            ->willReturn([$resourceType => [$resource => $features]]);

        $this->assertEquals($features, $this->configurationManager->getFeaturesByResource($resourceType, $resource));
    }

    public function testGetFeaturesByResourceWhenTheyDoesNotSet(): void
    {
        $this->configurationProvider->expects($this->once())
            ->method('getResourcesConfiguration')
            ->willReturn([]);

        $this->assertSame([], $this->configurationManager->getFeaturesByResource('testType', 'testResource'));
    }

    public function testGetFeatureDependencies(): void
    {
        $feature = 'feature3';
        $dependsOn = ['feature1', 'feature2'];

        $this->configurationProvider->expects($this->once())
            ->method('getDependenciesConfiguration')
            ->willReturn([$feature => $dependsOn]);

        $this->assertEquals($dependsOn, $this->configurationManager->getFeatureDependencies($feature));
    }

    public function testGetFeatureDependenciesWhenTheyDoesNotSet(): void
    {
        $this->configurationProvider->expects($this->once())
            ->method('getDependenciesConfiguration')
            ->willReturn([]);

        $this->assertSame([], $this->configurationManager->getFeatureDependencies('feature'));
    }

    public function testGetFeatureDependents(): void
    {
        $feature = 'feature1';
        $dependents = ['feature2', 'feature3'];

        $this->configurationProvider->expects($this->once())
            ->method('getDependentsConfiguration')
            ->willReturn([$feature => $dependents]);

        $this->assertEquals($dependents, $this->configurationManager->getFeatureDependents($feature));
    }

    public function testGetFeatureDependentsWhenTheyDoesNotSet(): void
    {
        $this->configurationProvider->expects($this->once())
            ->method('getDependentsConfiguration')
            ->willReturn([]);

        $this->assertSame([], $this->configurationManager->getFeatureDependents('feature'));
    }

    public function testGetFeatureByToggle(): void
    {
        $this->configurationProvider->expects($this->once())
            ->method('getTogglesConfiguration')
            ->willReturn(['toggle1' => 'feature1']);

        $this->assertEquals('feature1', $this->configurationManager->getFeatureByToggle('toggle1'));
    }

    public function testGetFeatureByToggleWhenItDoesNotSet(): void
    {
        $this->configurationProvider->expects($this->once())
            ->method('getTogglesConfiguration')
            ->willReturn(['toggle1' => 'feature1']);

        $this->assertNull($this->configurationManager->getFeatureByToggle('toggle2'));
    }

    /**
     * @dataProvider getResourcesByTypeProvider
     */
    public function testGetResourcesByType(string $resourceType, array $configuration, array $expectedResources): void
    {
        $this->configurationProvider->expects($this->once())
            ->method('getResourcesConfiguration')
            ->willReturn($configuration);

        $this->assertEquals($expectedResources, $this->configurationManager->getResourcesByType($resourceType));
    }

    public function getResourcesByTypeProvider(): array
    {
        return [
            'non existing resource' => [
                'nonExisting',
                [
                    'resource1' => ['feature1_1', 'feature1_2']
                ],
                []
            ],
            'existing resource'     => [
                'resource1',
                [
                    'resource1' => ['feature1_1', 'feature1_2'],
                    'resource2' => ['feature2_1', 'feature2_2'],
                ],
                ['feature1_1', 'feature1_2']
            ],
        ];
    }
}
