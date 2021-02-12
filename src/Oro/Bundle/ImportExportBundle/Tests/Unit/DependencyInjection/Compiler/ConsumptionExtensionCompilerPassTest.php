<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ConsumptionExtensionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ConsumptionExtensionCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    private Definition $definition;
    private ContainerBuilder $containerBuilder;
    private ConsumptionExtensionCompilerPass $compilerPass;

    protected function setUp(): void
    {
        $this->definition = $this->createMock(Definition::class);
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);

        $this->compilerPass = new ConsumptionExtensionCompilerPass();
    }

    public function testProcess(): void
    {
        $this->containerBuilder->expects(self::once())
            ->method('hasDefinition')
            ->with('oro_ui.consumption_extension.request_context')
            ->willReturn(true);

        $this->containerBuilder->expects(self::once())
            ->method('getDefinition')
            ->with('oro_ui.consumption_extension.request_context')
            ->willReturn($this->definition);

        $this->definition->expects(self::exactly(9))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addTopicName', [Topics::PRE_IMPORT]],
                ['addTopicName', [Topics::IMPORT]],
                ['addTopicName', [Topics::PRE_EXPORT]],
                ['addTopicName', [Topics::EXPORT]],
                ['addTopicName', [Topics::POST_EXPORT]],
                ['addTopicName', [Topics::SEND_IMPORT_NOTIFICATION]],
                ['addTopicName', [Topics::SAVE_IMPORT_EXPORT_RESULT]],
                ['addTopicName', [Topics::PRE_HTTP_IMPORT]],
                ['addTopicName', [Topics::HTTP_IMPORT]],
            );

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithoutDefinition(): void
    {
        $this->containerBuilder->expects(self::once())
            ->method('hasDefinition')
            ->with('oro_ui.consumption_extension.request_context')
            ->willReturn(false);

        $this->containerBuilder->expects(self::never())
            ->method('getDefinition');

        $this->definition->expects(self::never())
            ->method('addMethodCall');

        $this->compilerPass->process($this->containerBuilder);
    }
}
