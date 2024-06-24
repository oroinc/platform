<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UsageStatsCompilerPass;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\UsageStatsProviderRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UsageStatsCompilerPassTest extends TestCase
{
    public function testProcessNoTaggedServices(): void
    {
        $container = new ContainerBuilder();

        $providerRegistryDefinition = $container
            ->register(
                'oro_platform.provider.usage_stats.usage_stats_provider_registry',
                UsageStatsProviderRegistry::class
            )
            ->addArgument([]);

        $compiler = new UsageStatsCompilerPass(
            'oro_platform.provider.usage_stats.usage_stats_provider_registry',
            'oro_platform.usage_stats_provider'
        );
        $compiler->process($container);

        self::assertEquals([], $providerRegistryDefinition->getArgument(0));
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();

        $providerRegistryDefinition = $container
            ->register(
                'oro_platform.provider.usage_stats.usage_stats_provider_registry',
                UsageStatsProviderRegistry::class
            )
            ->addArgument([]);

        $container->register('provider_1')
            ->addTag('oro_platform.usage_stats_provider', ['priority' => -100]);
        $container->register('provider_2')
            ->addTag('oro_platform.usage_stats_provider');
        $container->register('provider_3')
            ->addTag('oro_platform.usage_stats_provider', ['priority' => 100]);

        $compiler = new UsageStatsCompilerPass(
            'oro_platform.provider.usage_stats.usage_stats_provider_registry',
            'oro_platform.usage_stats_provider'
        );
        $compiler->process($container);

        self::assertEquals(
            [
                new Reference('provider_3'),
                new Reference('provider_2'),
                new Reference('provider_1'),
            ],
            $providerRegistryDefinition->getArgument(0)
        );
    }
}
