<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ImportExportConfigurationRegistryCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ImportExportConfigurationRegistryCompilerPassTest extends TestCase
{
    /** @var ImportExportConfigurationRegistryCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ImportExportConfigurationRegistryCompilerPass();
    }

    public function testProcessWithoutRegistryService()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_importexport.configuration.registry');

        $container->register('service_1')
            ->addTag('oro_importexport.configuration', ['alias' => 'alias1', 'name' => 'name1'])
            ->addTag('oro_importexport.configuration', ['alias' => 'alias11', 'name' => 'name11']);
        $container->register('service_2')
            ->addTag('oro_importexport.configuration', ['alias' => 'alias2', 'name' => 'name2']);

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addConfiguration', [new Reference('service_1'), 'alias1']],
                ['addConfiguration', [new Reference('service_1'), 'alias11']],
                ['addConfiguration', [new Reference('service_2'), 'alias2']]
            ],
            $registryDef->getMethodCalls()
        );
    }

    public function testProcessWhenNoTaggedServices()
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_importexport.configuration.registry');

        $this->compiler->process($container);

        self::assertSame([], $registryDef->getMethodCalls());
    }
}
