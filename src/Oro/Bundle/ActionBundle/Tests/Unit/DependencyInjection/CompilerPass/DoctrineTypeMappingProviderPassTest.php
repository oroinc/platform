<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\DoctrineTypeMappingProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineTypeMappingProviderPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $container->register('type_mapping1')
            ->addTag('oro.action.extension.doctrine_type_mapping', []);
        $container->register('type_mapping2')
            ->addTag('oro.action.extension.doctrine_type_mapping', ['priority' => -10]);
        $container->register('type_mapping3')
            ->addTag('oro.action.extension.doctrine_type_mapping', ['priority' => 10]);

        $chainProvider = $container->register('oro_action.provider.doctrine_type_mapping');

        $compiler = new DoctrineTypeMappingProviderPass();
        $compiler->process($container);

        self::assertEquals(
            [
                ['addExtension', [new Reference('type_mapping2'), 'type_mapping2']],
                ['addExtension', [new Reference('type_mapping1'), 'type_mapping1']],
                ['addExtension', [new Reference('type_mapping3'), 'type_mapping3']]
            ],
            $chainProvider->getMethodCalls()
        );
    }
}
