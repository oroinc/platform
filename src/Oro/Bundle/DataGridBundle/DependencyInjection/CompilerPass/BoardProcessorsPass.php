<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class BoardProcessorsPass implements CompilerPassInterface
{
    const BOARD_EXTENSION_SERVICE = 'oro_datagrid.extension.board';
    const TAG = 'oro_datagrid.board_processor';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::BOARD_EXTENSION_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (empty($taggedServices)) {
            return;
        }

        $definition = $container->getDefinition(self::BOARD_EXTENSION_SERVICE);

        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall(
                'addProcessor',
                [new Reference($id)]
            );
        }
    }
}
