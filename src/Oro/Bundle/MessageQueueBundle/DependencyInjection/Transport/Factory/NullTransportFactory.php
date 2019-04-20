<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory;

use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * This class configures the container services for working with NULL message queue transport.
 */
class NullTransportFactory implements TransportFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, array $config)
    {
        $connectionId = sprintf('oro_message_queue.transport.%s.connection', $this->getKey());
        $connection = new Definition(NullConnection::class);

        $container->setDefinition($connectionId, $connection);

        return $connectionId;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'null';
    }

    /**
     * {@inheritdoc}
     *
     * @param ArrayNodeDefinition $builder
     */
    public function addConfiguration(NodeDefinition $builder)
    {
        $builder
            ->children()
                ->arrayNode($this->getKey())
                    ->addDefaultsIfNotSet()
                    ->info('NULL transport configuration.')
                ->end()
            ->end()
        ;
    }
}
