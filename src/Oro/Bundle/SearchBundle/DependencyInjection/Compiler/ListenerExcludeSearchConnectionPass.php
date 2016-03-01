<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass;

class ListenerExcludeSearchConnectionPass implements CompilerPassInterface
{
    const SEARCH_CONNECTION_NAME = 'search';
    const GENERATED_VALUE_STRATEGY_LISTENER = 'oro_entity.listener.orm.generated_value_strategy_listener';

    /** {@inheritdoc} */
    public function process(ContainerBuilder $container)
    {
        $connections = [];
        if ($container->hasParameter(UpdateDoctrineEventHandlersPass::DOCTRINE_EXCLUDE_LISTENER_CONNECTIONS_PARAM)) {
            $connections = (array)$container
                ->getParameter(UpdateDoctrineEventHandlersPass::DOCTRINE_EXCLUDE_LISTENER_CONNECTIONS_PARAM);
        }

        $connections[] = self::SEARCH_CONNECTION_NAME;

        $container
            ->setParameter(UpdateDoctrineEventHandlersPass::DOCTRINE_EXCLUDE_LISTENER_CONNECTIONS_PARAM, $connections);
    }
}
