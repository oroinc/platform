<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Forces Doctrine's listeners and subscribers to use the default connection
 * when the connection for them is not specified explicitly.
 */
class UpdateDoctrineEventHandlersPass implements CompilerPassInterface
{
    private const DOCTRINE_CONNECTIONS_PARAM = 'doctrine.connections';

    private const DOCTRINE_EVENT_SUBSCRIBER_TAG = 'doctrine.event_subscriber';
    private const DOCTRINE_EVENT_LISTENER_TAG   = 'doctrine.event_listener';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $connections = $this->getAllConnections($container);
        if ($connections) {
            $customConnections = array_filter($connections, function ($connection) {
                return $connection !== 'default';
            });
            $this->processDoctrineEventHandlers(self::DOCTRINE_EVENT_SUBSCRIBER_TAG, $container, $customConnections);
            $this->processDoctrineEventHandlers(self::DOCTRINE_EVENT_LISTENER_TAG, $container, $customConnections);
        }
    }

    /**
     * @return string[]
     */
    private function getAllConnections(ContainerBuilder $container): array
    {
        if (!$container->hasParameter(self::DOCTRINE_CONNECTIONS_PARAM)) {
            return [];
        }

        return array_keys((array)$container->getParameter(self::DOCTRINE_CONNECTIONS_PARAM));
    }

    /**
     * @param string           $handlerTag
     * @param ContainerBuilder $container
     * @param string[]         $customConnections
     */
    private function processDoctrineEventHandlers(
        string $handlerTag,
        ContainerBuilder $container,
        array $customConnections
    ): void {
        $taggedServices = $container->findTaggedServiceIds($handlerTag);
        foreach ($taggedServices as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->clearTag($handlerTag);
            foreach ($tags as $tag) {
                if (empty($tag['connection'])) {
                    $tag['connection'] = 'default';
                } elseif ('default' === $tag['connection']) {
                    $definition->setDeprecated(
                        true,
                        sprintf(
                            'Passing "connection: default" to "%%service_id%%" tags is default behaviour now.'
                            . ' Specify one of "%s" or remove default one.',
                            implode(', ', $customConnections)
                        )
                    );
                }

                $definition->addTag($handlerTag, $tag);
            }
        }
    }
}
