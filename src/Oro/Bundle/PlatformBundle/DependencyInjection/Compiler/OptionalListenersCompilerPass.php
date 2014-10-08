<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OptionalListenersCompilerPass implements CompilerPassInterface
{
    const OPTIONAL_LISTENER_TAG = 'oro.optional_listener';
    const OPTIONAL_LISTENER_MANAGER = 'oro_platform.optional_listeners.manager';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = array_keys(
            $container->findTaggedServiceIds(self::OPTIONAL_LISTENER_TAG)
        );

        $definition = $container->getDefinition(self::OPTIONAL_LISTENER_MANAGER);
        $definition->replaceArgument(0, $taggedServices);
    }
}
