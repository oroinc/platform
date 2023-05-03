<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationPass;
use Oro\Bundle\ConfigBundle\Tests\Unit\Fixtures\TestBundle;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SystemConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var SystemConfigurationPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerBuilder::class);

        $this->compiler = new SystemConfigurationPass();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcess()
    {
        $bundle = new TestBundle();
        $bundles = [$bundle->getName() => get_class($bundle)];
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($bundles);
        $this->container->expects($this->once())
            ->method('getExtensions')
            ->willReturn(['test_bundle' => null]);

        $config = [
            'resolved' => true,
            'some_field' => ['value' => 'some_val', 'scope' => 'app'],
            'some_another_field' => ['value' => 'some_another_val'],
            'service_another_field' => ['value' => null],
        ];

        $this->container->expects($this->once())
            ->method('getExtensionConfig')
            ->with('test_bundle')
            ->willReturn([
                [
                    'settings' => $config,
                ]
            ]);

        $defaultProviderMock = $this->createMock(Definition::class);

        $bagServiceDef = $this->createMock(Definition::class);
        $configBagServiceDef = $this->createMock(Definition::class);
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn([
                'first_scope_service'  => [
                    ['scope' => 'app', 'priority' => 100],
                ],
                'second_scope_service' => [
                    ['scope' => 'user', 'priority' => -100],
                ],
            ]);
        $apiManagerServiceDef = $this->createMock(Definition::class);
        $configManagerServiceDef = $this->createMock(Definition::class);
        $this->container->expects($this->exactly(4))
            ->method('getDefinition')
            ->willReturnMap([
                ['oro_config.config_definition_bag', $bagServiceDef],
                ['oro_config.config_bag', $configBagServiceDef],
                ['oro_config.manager.api', $apiManagerServiceDef],
                ['oro_config.manager', $configManagerServiceDef],
                ['oro_config.default_provider', $defaultProviderMock],
            ]);
        $apiManagerServiceDef->expects($this->exactly(2))
            ->method('addMethodCall');

        $bagServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with($this->equalTo(0), ['test_bundle' => $config]);
        $configBagServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with($this->equalTo(0), $this->isType('array'))
            ->willReturnCallback(function ($index, $argument) {
                self::assertEquals(
                    ['Test\Class::method'],
                    $argument['groups']['group_with_scalar_configurator_and_handler']['configurator']
                );
                self::assertEquals(
                    ['Test\Class::method'],
                    $argument['groups']['group_with_scalar_configurator_and_handler']['handler']
                );
                self::assertEquals(
                    ['Test\Class::method'],
                    $argument['groups']['group_with_array_configurator_and_handler']['configurator']
                );
                self::assertEquals(
                    ['Test\Class::method'],
                    $argument['groups']['group_with_array_configurator_and_handler']['handler']
                );
            });

        $mainServiceAlias = $this->createMock(Alias::class);
        $mainServiceAlias->expects($this->once())
            ->method('setPublic')
            ->with(true);

        $this->container->expects($this->once())
            ->method('setAlias')
            ->with('oro_config.manager', 'oro_config.app');

        $this->container->expects($this->once())
            ->method('getAlias')
            ->with('oro_config.manager')
            ->willReturn($mainServiceAlias);

        $this->compiler->process($this->container);
    }

    public function testProcessDefaultValueProvider()
    {
        $bundle = new TestBundle();
        $bundles = [$bundle->getName() => get_class($bundle)];
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($bundles);
        $this->container->expects(self::once())
            ->method('getExtensions')
            ->willReturn(['test_bundle' => null]);

        $this->container->expects(self::once())
            ->method('getExtensionConfig')
            ->with('test_bundle')
            ->willReturn([
                [
                    'settings' => [
                        'resolved' => true,
                        'some_field' => ['value' => 'some_val', 'scope' => 'app'],
                        'some_another_field' => ['value' => 'some_another_val'],
                        'service_another_field' => ['value' => '@oro_config.default_provider'],
                    ],
                ]
            ]);

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_config.default_provider')
            ->willReturn(true);

        $defaultProviderMock = $this->createMock(Definition::class);

        $bagServiceDef = $this->createMock(Definition::class);
        $configBagServiceDef = $this->createMock(Definition::class);
        $this->container->expects(self::once())
            ->method('findTaggedServiceIds')
            ->willReturn([
                'first_scope_service'  => [
                    ['scope' => 'app', 'priority' => 100],
                ],
                'second_scope_service' => [
                    ['scope' => 'user', 'priority' => -100],
                ],
            ]);

        $apiManagerServiceDef = $this->createMock(Definition::class);
        $configManagerServiceDef = $this->createMock(Definition::class);
        $this->container->expects(self::exactly(5))
            ->method('getDefinition')
            ->willReturnMap([
                ['oro_config.config_definition_bag', $bagServiceDef],
                ['oro_config.config_bag', $configBagServiceDef],
                ['oro_config.manager.api', $apiManagerServiceDef],
                ['oro_config.manager', $configManagerServiceDef],
                ['oro_config.default_provider', $defaultProviderMock],
            ]);

        $bagServiceDef->expects($this->once())
            ->method('replaceArgument')
            ->with($this->equalTo(0), [
                'test_bundle' => [
                    'resolved' => true,
                    'some_field' => ['value' => 'some_val', 'scope' => 'app'],
                    'some_another_field' => ['value' => 'some_another_val'],
                    'service_another_field' => ['value' => $defaultProviderMock],
                ],
            ]);

        $mainServiceAlias = $this->createMock(Alias::class);
        $mainServiceAlias->expects($this->once())
            ->method('setPublic')
            ->with(true);

        $this->container->expects($this->once())
            ->method('setAlias')
            ->with('oro_config.manager', 'oro_config.app');

        $this->container->expects($this->once())
            ->method('getAlias')
            ->with('oro_config.manager')
            ->willReturn($mainServiceAlias);

        $this->compiler->process($this->container);
    }
}
