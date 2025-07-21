<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\DoctrineConnectionPingPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DoctrineConnectionPingPassTest extends TestCase
{
    public function testProcessNoDefinition(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects(self::once())
            ->method('hasDefinition')
            ->with('oro_sync.periodic.db_ping')
            ->willReturn(false);

        $container->expects(self::never())
            ->method('getDefinition')
            ->withAnyParameters();

        (new DoctrineConnectionPingPass('DummyConnection'))->process($container);
    }

    public function testProcess(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects(self::once())
            ->method('hasDefinition')
            ->with('oro_sync.periodic.db_ping')
            ->willReturn(true);

        $definition = $this->createMock(Definition::class);
        $container->expects(self::once())
            ->method('getDefinition')
            ->with('oro_sync.periodic.db_ping')
            ->willReturn($definition);

        $definition->expects(self::once())
            ->method('addMethodCall')
            ->with('addDoctrineConnectionName', ['DummyConnection']);

        (new DoctrineConnectionPingPass('DummyConnection'))->process($container);
    }
}
