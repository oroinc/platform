<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class InlineHandlerPass implements CompilerPassInterface
{
    const INLINE_EXTENSION_HANDLER_PROCESSOR_SERVICE = 'oro_datagrid.extension.inline.handler_processor';
    const TAG = 'oro_datagrid.extension.api.handler';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::INLINE_EXTENSION_HANDLER_PROCESSOR_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (empty($taggedServices)) {
            return;
        }

        $definition = $container->getDefinition(self::INLINE_EXTENSION_HANDLER_PROCESSOR_SERVICE);

        foreach (array_keys($taggedServices) as $id) {
            $definition->addHandler(
                'addHandler',
                [new Reference($id)]
            );
        }
    }
}
