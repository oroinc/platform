<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\PubSubRouterCachePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class PubSubRouterCachePassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessWhenNoDefinition(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with('gos_pubsub_router.php_file.cache')
            ->willReturn(false);

        $containerBuilder
            ->expects($this->never())
            ->method('getDefinition');

        (new PubSubRouterCachePass())->process($containerBuilder);
    }

    public function testProcess(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with($service = 'gos_pubsub_router.php_file.cache')
            ->willReturn(true);

        $containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with($service)
            ->willReturn($definition = $this->createMock(Definition::class));

        $definition
            ->expects($this->once())
            ->method('setArgument')
            ->with(0, '%kernel.cache_dir%/oro_data');

        (new PubSubRouterCachePass())->process($containerBuilder);
    }
}
