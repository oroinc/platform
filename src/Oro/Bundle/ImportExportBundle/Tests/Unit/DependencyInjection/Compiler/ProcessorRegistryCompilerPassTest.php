<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ProcessorRegistryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

class ProcessorRegistryCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessorRegistryCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ProcessorRegistryCompilerPass();
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $registryDef = $container->register('oro_importexport.processor.registry');

        $container->register('oro_test.foo_import_processor')
            ->addTag(
                'oro_importexport.processor',
                ['type' => 'import', 'entity' => \stdClass::class, 'alias' => 'foo_import']
            );
        $container->register('oro_test.foo_export_processor')
            ->addTag(
                'oro_importexport.processor',
                ['type' => 'export', 'entity' => \stdClass::class, 'alias' => 'foo_export']
            );
        $container->register('oro_test.bar_import_processor')
            ->addTag(
                'oro_importexport.processor',
                ['type' => 'import', 'entity' => 'BarEntity', 'alias' => 'bar_import']
            );
        $container->register('oro_test.bar_export_processor')
            ->addTag(
                'oro_importexport.processor',
                ['type' => 'export', 'entity' => 'BarEntity', 'alias' => 'bar_export']
            );

        $this->compiler->process($container);

        self::assertEquals(
            [
                [
                    'registerProcessor',
                    [new Reference('oro_test.foo_import_processor'), 'import', \stdClass::class, 'foo_import']
                ],
                [
                    'registerProcessor',
                    [new Reference('oro_test.foo_export_processor'), 'export', \stdClass::class, 'foo_export']
                ],
                [
                    'registerProcessor',
                    [new Reference('oro_test.bar_import_processor'), 'import', 'BarEntity', 'bar_import']
                ],
                [
                    'registerProcessor',
                    [new Reference('oro_test.bar_export_processor'), 'export', 'BarEntity', 'bar_export']
                ]
            ],
            $registryDef->getMethodCalls()
        );
    }

    public function testProcessWhenTypeAttributeIsMissing()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Tag "oro_importexport.processor" for service "processor1" must have attribute "type"'
        );

        $container = new ContainerBuilder();
        $container->register('processor1')
            ->addTag(
                'oro_importexport.processor',
                ['alias' => 'foo_import', 'entity' => \stdClass::class]
            );

        $this->compiler->process($container);
    }

    public function testProcessWhenEntityAttributeIsMissing()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Tag "oro_importexport.processor" for service "processor1" must have attribute "entity"'
        );

        $container = new ContainerBuilder();
        $container->register('processor1')
            ->addTag(
                'oro_importexport.processor',
                ['type' => 'import', 'alias' => 'foo_import']
            );

        $this->compiler->process($container);
    }

    public function testProcessWhenAliasAttributeIsMissing()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Tag "oro_importexport.processor" for service "processor1" must have attribute "alias"'
        );

        $container = new ContainerBuilder();
        $container->register('processor1')
            ->addTag(
                'oro_importexport.processor',
                ['type' => 'import', 'entity' => \stdClass::class]
            );

        $this->compiler->process($container);
    }
}
