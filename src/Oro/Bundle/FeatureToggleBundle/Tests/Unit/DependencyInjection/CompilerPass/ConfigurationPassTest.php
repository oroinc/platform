<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Doctrine\Common\Cache\ClearableCache;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\ConfigurationPass;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigurationPass
     */
    protected $configurationPass;

    protected function setUp()
    {
        $this->configurationPass = new ConfigurationPass();
    }

    public function testProcess()
    {
        $expectedConfiguration = [
            TestBundle1::class => [
                'feature1' => [
                    'label' => 'Feature 1',
                    'toggle' => 'toggle1',
                    'description' => 'Description 1',
                    'dependencies' => ['feature2'],
                    'routes' => ['f1_route1', 'f1_route2'],
                    'configuration' => ['config_section1', 'config_leaf1'],
                ]
            ],
            TestBundle2::class => [
                'feature1' => [
                    'toggle' => 'changed_toggle',
                    'routes' => ['f1_route3'],
                    'configuration' => ['config_leaf2'],
                ],
                'feature2' => [
                    'label' => 'Feature 2',
                    'toggle' => 'toggle2'
                ]
            ]
        ];

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container **/
        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ConfigurationPass::EXTENSION_TAG)
            ->willReturn(['testConfigExtension' => []]);

        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();

        $cache = $this->getMock(ClearableCache::class);
        $cache->expects($this->once())
            ->method('deleteAll')
            ->willReturn(true);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(
                [
                    $bundle1->getName() => TestBundle1::class,
                    $bundle2->getName() => TestBundle2::class
                ]
            );

        $providerDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $providerDefinition->expects($this->once())
            ->method('replaceArgument')
            ->with(0, $expectedConfiguration);

        $configDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $configDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('addExtension', [new Reference('testConfigExtension')]);

        $container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->withConsecutive(
                [ConfigurationPass::CONFIGURATION_SERVICE],
                [ConfigurationPass::PROVIDER]
            )
            ->willReturn(true);
        $container->expects($this->exactly(2))
            ->method('getDefinition')
            ->withConsecutive(
                [ConfigurationPass::CONFIGURATION_SERVICE],
                [ConfigurationPass::PROVIDER]
            )
            ->willReturnMap(
                [
                    [ConfigurationPass::CONFIGURATION_SERVICE, $configDefinition],
                    [ConfigurationPass::PROVIDER, $providerDefinition]
                ]
            );
        $container->expects($this->once())
            ->method('has')
            ->with(ConfigurationPass::CACHE)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with(ConfigurationPass::CACHE)
            ->willReturn($cache);

        $this->configurationPass->process($container);
    }
}
