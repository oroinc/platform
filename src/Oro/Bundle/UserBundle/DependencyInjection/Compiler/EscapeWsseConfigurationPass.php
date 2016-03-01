<?php

namespace Oro\Bundle\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EscapeWsseConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('escape_wsse_authentication.provider')) {
            $definition = $container->getDefinition('escape_wsse_authentication.provider');
            $definition->addMethodCall('setTokenFactory', [new Reference('oro_user.token.factory.wsse')]);
        }
    }
}
