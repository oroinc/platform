<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\SelectedFieldsProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SelectedFieldsProviderPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder
            ->expects(self::once())
            ->method('hasDefinition')
            ->with('oro_datagrid.provider.selected_fields')
            ->willReturn(true);

        $containerBuilder
            ->expects(self::once())
            ->method('getDefinition')
            ->with('oro_datagrid.provider.selected_fields')
            ->willReturn($compositeProviderDefinition = $this->createMock(Definition::class));

        $taggedServices = ['sample_service1' => [], 'sample_service2' => []];
        $containerBuilder
            ->expects(self::once())
            ->method('findTaggedServiceIds')
            ->with('oro_datagrid.selected_fields_provider')
            ->willReturn($taggedServices);

        $compositeProviderDefinition
            ->expects(self::exactly(2))
            ->method('addMethodCall')
            ->willReturnCallback(function (string $method, array $arguments) use ($taggedServices) {
                self::assertEquals('addSelectedFieldsProvider', $method);
                self::assertArrayHasKey((string)$arguments[0], $taggedServices);
            });

        (new SelectedFieldsProvidersPass())->process($containerBuilder);
    }

    public function testProcessWhenNoTaggedServices(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder
            ->expects(self::once())
            ->method('hasDefinition')
            ->with('oro_datagrid.provider.selected_fields')
            ->willReturn(true);

        $containerBuilder
            ->expects(self::once())
            ->method('findTaggedServiceIds')
            ->with('oro_datagrid.selected_fields_provider')
            ->willReturn([]);

        $containerBuilder
            ->expects(self::once())
            ->method('getDefinition')
            ->with('oro_datagrid.provider.selected_fields')
            ->willReturn($compositeProviderDefinition = $this->createMock(Definition::class));

        $compositeProviderDefinition
            ->expects(self::never())
            ->method('addMethodCall');

        (new SelectedFieldsProvidersPass())->process($containerBuilder);
    }

    public function testProcessWhenNoCompositeService(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder
            ->expects(self::once())
            ->method('hasDefinition')
            ->with('oro_datagrid.provider.selected_fields')
            ->willReturn(false);

        $containerBuilder
            ->expects(self::never())
            ->method('getDefinition');

        (new SelectedFieldsProvidersPass())->process($containerBuilder);
    }
}
