<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass;

class ListenerExcludeConfigConnectionPass implements CompilerPassInterface
{
    const CONFIG_CONNECTION_NAME = 'config';

    /** {@inheritdoc} */
    public function process(ContainerBuilder $container)
    {
        $connections = [];
        if ($container->hasParameter(UpdateDoctrineEventHandlersPass::DOCTRINE_EXCLUDE_LISTENER_CONNECTIONS_PARAM)) {
            $connections = (array)$container
                ->getParameter(UpdateDoctrineEventHandlersPass::DOCTRINE_EXCLUDE_LISTENER_CONNECTIONS_PARAM);
        }

        $connections[] = self::CONFIG_CONNECTION_NAME;

        $container
            ->setParameter(UpdateDoctrineEventHandlersPass::DOCTRINE_EXCLUDE_LISTENER_CONNECTIONS_PARAM, $connections);
    }
}
