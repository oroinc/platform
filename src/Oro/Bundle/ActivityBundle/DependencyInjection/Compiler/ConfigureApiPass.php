<?php

namespace Oro\Bundle\ActivityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures API related services.
 */
class ConfigureApiPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('oro_api.cache_manager')
            ->addMethodCall(
                'addResettableService',
                [new Reference('oro_activity.api.activity_association_provider')]
            );
    }
}
