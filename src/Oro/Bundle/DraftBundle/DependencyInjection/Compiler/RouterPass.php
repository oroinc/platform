<?php

namespace Oro\Bundle\DraftBundle\DependencyInjection\Compiler;

use Oro\Bundle\DraftBundle\Route\Router;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Replaces thr class of the oro_ui.router service to draft router.
 */
class RouterPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('oro_ui.router')
            ->setClass(Router::class)
            ->addMethodCall('setConfigManager', [new Reference('oro_entity_config.config_manager')]);
    }
}
