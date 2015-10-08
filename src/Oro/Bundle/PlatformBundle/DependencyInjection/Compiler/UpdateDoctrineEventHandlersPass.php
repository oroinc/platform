<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class UpdateDoctrineEventHandlersPass implements CompilerPassInterface
{
    const CONFIG_CONNECTION_NAME = 'config';
    const SESSION_CONNECTION_NAME = 'session';
    const DOCTRINE_CONNECTIONS_PARAM = 'doctrine.connections';
    const DOCTRINE_EVENT_SUBSCRIBER_TAG = 'doctrine.event_subscriber';
    const DOCTRINE_EVENT_LISTENER_TAG = 'doctrine.event_listener';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $connections = $this->getConnections($container);
        if (!empty($connections)) {
            $this->processDoctrineEventHandlers(self::DOCTRINE_EVENT_SUBSCRIBER_TAG, $container, $connections);
            $this->processDoctrineEventHandlers(self::DOCTRINE_EVENT_LISTENER_TAG, $container, $connections);
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    protected function getConnections(ContainerBuilder $container)
    {
        if (!$container->hasParameter(self::DOCTRINE_CONNECTIONS_PARAM)) {
            return [];
        }

        $connections = $container->getParameter(self::DOCTRINE_CONNECTIONS_PARAM);
        unset($connections[self::CONFIG_CONNECTION_NAME]);
        unset($connections[self::SESSION_CONNECTION_NAME]);

        return array_keys($connections);
    }

    /**
     * @param string           $handlerTag
     * @param ContainerBuilder $container
     * @param string[]         $connections
     */
    protected function processDoctrineEventHandlers($handlerTag, ContainerBuilder $container, $connections)
    {
        $taggedServices = $container->findTaggedServiceIds($handlerTag);
        foreach ($taggedServices as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->clearTag($handlerTag);
            foreach ($tags as $tag) {
                if (!isset($tag['connection']) || null === $tag['connection']) {
                    foreach ($connections as $connection) {
                        $tag['connection'] = $connection;
                        $definition->addTag($handlerTag, $tag);
                    }
                } else {
                    $definition->addTag($handlerTag, $tag);
                }
            }
        }
    }
}
