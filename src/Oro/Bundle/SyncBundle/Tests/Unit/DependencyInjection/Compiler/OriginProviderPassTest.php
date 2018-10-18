<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\OriginProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OriginProviderPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder
            ->expects(self::once())
            ->method('getDefinition')
            ->with('oro_sync.authentication.origin.origin_provider_chain')
            ->willReturn($chainDefinition = $this->createMock(Definition::class));

        $taggedServices = ['sampleService1' => [], 'sampleService2' => []];
        $containerBuilder
            ->expects(self::once())
            ->method('findTaggedServiceIds')
            ->with('oro_sync.origin_provider')
            ->willReturn($taggedServices);

        $chainDefinition
            ->expects(self::exactly(2))
            ->method('addMethodCall')
            ->willReturnCallback(function (string $method, array $arguments) use ($taggedServices) {
                self::assertEquals('addProvider', $method);
                self::assertArrayHasKey((string)$arguments[0], $taggedServices);
            });

        (new OriginProviderPass())->process($containerBuilder);
    }
}
