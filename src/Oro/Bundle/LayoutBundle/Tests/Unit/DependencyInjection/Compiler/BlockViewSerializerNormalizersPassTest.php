<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\BlockViewSerializerNormalizersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BlockViewSerializerNormalizersPassTest extends \PHPUnit\Framework\TestCase
{
    private BlockViewSerializerNormalizersPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BlockViewSerializerNormalizersPass();
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $serializerDef = $container->register('oro_layout.block_view_serializer')
            ->addArgument([]);

        $container->register('normalizer_1')
            ->addTag('layout.block_view_serializer.normalizer');

        $this->compiler->process($container);

        $this->assertEquals(
            [new Reference('normalizer_1')],
            $serializerDef->getArgument(0)
        );
    }
}
