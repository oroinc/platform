<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConfigurationPass;
use Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;

use Oro\Component\Config\CumulativeResourceManager;

class ConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Definition */
    protected $configProviderDefinition;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider */
    protected $cacheProvider;

    /** @var ConfigurationPass */
    protected $compilerPass;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProviderDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['deleteAll'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->compilerPass = new ConfigurationPass();
    }

    protected function tearDown()
    {
        unset($this->compilerPass, $this->cacheProvider, $this->configProviderDefinition, $this->container);
    }

    public function testProcess()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1->getName() => get_class($bundle1), $bundle2->getName() => get_class($bundle2)]);

        $this->cacheProvider->expects($this->once())
            ->method('deleteAll')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(ConfigurationPass::PROVIDER_SERVICE_ID)
            ->willReturn(true);
        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with(ConfigurationPass::PROVIDER_SERVICE_ID)
            ->willReturn($this->configProviderDefinition);
        $this->container->expects($this->once())
            ->method('get')
            ->with(ConfigurationPass::CACHE_SERVICE_ID)
            ->willReturn($this->cacheProvider);

        $result = null;

        $this->configProviderDefinition->expects($this->once())
            ->method('replaceArgument')
            ->willReturnCallback(
                function ($index, $argument) use (&$result) {
                    $this->assertEquals(3, $index);

                    $result = $argument;
                }
            );

        $this->compilerPass->process($this->container);

        $this->assertEquals(
            [
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1' => [
                    'test_action1' => [
                        'label' => 'Test Action 1'
                    ],
                    'test_action2' => [
                        'label' => 'Test Action 2'
                    ],
                ],
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2' => [
                    'test_action4' => [
                        'label' => 'Test Action 4'
                    ]
                ]
            ],
            $result
        );
    }

    public function testProcessWithoutConfigurationProvider()
    {
        $this->cacheProvider->expects($this->once())
            ->method('deleteAll')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(ConfigurationPass::PROVIDER_SERVICE_ID)
            ->willReturn(false);
        $this->container->expects($this->never())
            ->method('getDefinition')
            ->with(ConfigurationPass::PROVIDER_SERVICE_ID);
        $this->container->expects($this->once())
            ->method('get')
            ->with(ConfigurationPass::CACHE_SERVICE_ID)
            ->willReturn($this->cacheProvider);

        $this->compilerPass->process($this->container);
    }
}
