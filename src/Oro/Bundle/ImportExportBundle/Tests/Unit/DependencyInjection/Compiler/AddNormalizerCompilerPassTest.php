<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\AddNormalizerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddNormalizerCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var AddNormalizerCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new AddNormalizerCompilerPass();
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $serializerDef = $container->register('oro_importexport.serializer')
            ->setArguments([[], []]);

        $container->register('normalizer_1')
            ->addTag('oro_importexport.normalizer', ['priority' => -100]);
        $container->register('normalizer_2')
            ->addTag('oro_importexport.normalizer');
        $container->register('normalizer_3')
            ->addTag('oro_importexport.normalizer', ['priority' => 100]);

        $container->register('encoder_1')
            ->addTag('serializer.encoder', ['priority' => -100]);
        $container->register('encoder_2')
            ->addTag('serializer.encoder');
        $container->register('encoder_3')
            ->addTag('serializer.encoder', ['priority' => 100]);

        $this->compiler->process($container);

        self::assertEquals(
            [
                new Reference('normalizer_3'),
                new Reference('normalizer_2'),
                new Reference('normalizer_1')
            ],
            $serializerDef->getArgument(0)
        );
        self::assertEquals(
            [
                new Reference('encoder_3'),
                new Reference('encoder_2'),
                new Reference('encoder_1')
            ],
            $serializerDef->getArgument(1)
        );
    }

    public function testProcessWhenEncodersInjectedInConstructor()
    {
        $container = new ContainerBuilder();
        $serializerDef = $container->register('oro_importexport.serializer')
            ->setArguments([
                [],
                [new Reference('existing_encoder_1'), new Reference('existing_encoder_2')]
            ]);

        $container->register('normalizer_1')
            ->addTag('oro_importexport.normalizer');

        $container->register('encoder_1')
            ->addTag('serializer.encoder', ['priority' => -100]);
        $container->register('encoder_2')
            ->addTag('serializer.encoder');
        $container->register('encoder_3')
            ->addTag('serializer.encoder', ['priority' => 100]);

        $this->compiler->process($container);

        self::assertEquals([new Reference('normalizer_1')], $serializerDef->getArgument(0));
        self::assertEquals(
            [
                new Reference('existing_encoder_1'),
                new Reference('existing_encoder_2'),
                new Reference('encoder_3'),
                new Reference('encoder_2'),
                new Reference('encoder_1')
            ],
            $serializerDef->getArgument(1)
        );
    }

    public function testProcessFailsWhenNoNormalizers()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'You must tag at least one service as "oro_importexport.normalizer"'
            . ' to use the import export Serializer service'
        );

        $container = new ContainerBuilder();
        $container->register('oro_importexport.serializer')
            ->setArguments([[], []]);

        $container->register('encoder_1')
            ->addTag('serializer.encoder');

        $this->compiler->process($container);
    }

    public function testProcessFailsWhenNoEncoders()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'You must tag at least one service as "serializer.encoder" to use the import export Serializer service'
        );

        $container = new ContainerBuilder();
        $container->register('oro_importexport.serializer')
            ->setArguments([[], []]);

        $container->register('normalizer_1')
            ->addTag('oro_importexport.normalizer');

        $this->compiler->process($container);
    }
}
