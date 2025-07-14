<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\ProcessorsPass;
use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProcessorsPassTest extends TestCase
{
    private CompilerPassInterface $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new ProcessorsPass();
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_integration.processor_registry');

        $container->register('processor_1')
            ->addTag('oro_integration.sync_processor', ['integration' => 'test']);

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addProcessor', ['test', new Reference('processor_1')]]
            ],
            $registryDef->getMethodCalls()
        );
    }

    public function testProcessWhenNoIntegrationType(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Could not retrieve type attribute for "processor_1"');

        $container = new ContainerBuilder();
        $container->register('oro_integration.processor_registry');

        $container->register('processor_1')
            ->addTag('oro_integration.sync_processor');

        $this->compiler->process($container);
    }
}
