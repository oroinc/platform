<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\VirtualFieldProvidersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class VirtualFieldProvidersCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $container->register('provider1')
            ->addTag('oro_entity.virtual_field_provider', []);
        $container->register('provider2')
            ->addTag('oro_entity.virtual_field_provider', ['priority' => -10]);
        $container->register('provider3')
            ->addTag('oro_entity.virtual_field_provider', ['priority' => 10]);

        $chainProvider = $container->register('oro_entity.virtual_field_provider.chain')
            ->setArgument(0, []);

        $compiler = new VirtualFieldProvidersCompilerPass();
        $compiler->process($container);

        self::assertEquals(
            [
                new Reference('provider2'),
                new Reference('provider1'),
                new Reference('provider3')
            ],
            $chainProvider->getArgument(0)
        );
    }
}
