<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;

class ConfigurationManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationProvider;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    protected function setUp()
    {
        $this->configurationProvider = $this->getMockBuilder(ConfigurationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configurationManager = new ConfigurationManager($this->configurationProvider);
    }

    public function testGetDefault()
    {
        $feature = 'feature';
        $node = 'node';
        $default = 'default';

        $this->configurationProvider->expects($this->once())
            ->method('getFeaturesConfiguration')
            ->willReturn([]);

        $this->assertEquals($default, $this->configurationManager->get($feature, $node, $default));
    }

    public function testGet()
    {
        $feature = 'feature';
        $node = 'node';
        $default = 'default';
        $value = 'value';

        $this->configurationProvider->expects($this->once())
            ->method('getFeaturesConfiguration')
            ->willReturn(['feature' => ['node' => $value]]);

        $this->assertEquals($value, $this->configurationManager->get($feature, $node, $default));
    }

    public function testGetFeaturesByResource()
    {
        $resourceType = 'testType';
        $resource = 'testResource';
        $features = ['feature1', 'feature2'];

        $this->configurationProvider->expects($this->once())
            ->method('getResourcesConfiguration')
            ->willReturn([$resourceType => [$resource =>$features ]]);

        $this->assertEquals($features, $this->configurationManager->getFeaturesByResource($resourceType, $resource));
    }

    public function testGetFeatureDependencies()
    {
        $feature = 'feature3';
        $dependsOn = ['feature1', 'feature2'];

        $this->configurationProvider->expects($this->once())
            ->method('getDependenciesConfiguration')
            ->willReturn([$feature => $dependsOn]);

        $this->assertEquals($dependsOn, $this->configurationManager->getFeatureDependencies($feature));
    }

    public function testGetFeatureDependents()
    {
        $feature = 'feature1';
        $dependents = ['feature2', 'feature3'];

        $this->configurationProvider->expects($this->once())
            ->method('getDependentsConfiguration')
            ->willReturn([$feature => $dependents]);

        $this->assertEquals($dependents, $this->configurationManager->getFeatureDependents($feature));
    }

    public function testGetFeatureByToggle()
    {
        $feature = 'feature1';
        $toggle = 'oro_bundle.toggle_key';

        $this->configurationProvider->expects($this->once())
            ->method('getFeaturesConfiguration')
            ->willReturn([$feature => ['toggle' => $toggle]]);

        $this->assertSame($feature, $this->configurationManager->getFeatureByToggle($toggle));
    }

    public function testGetFeatureByWrongToggle()
    {
        $this->configurationProvider->expects($this->once())
            ->method('getFeaturesConfiguration')
            ->willReturn(['feature1' => ['toggle' => 'oro_bundle.toggle_key']]);

        $this->assertNull($this->configurationManager->getFeatureByToggle('wrong_toggle_key'));
    }
}
