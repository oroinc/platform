<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * TransportFactoryInterface is the interface for all message queue transports.
 */
interface TransportFactoryInterface
{
    /**
     * Configures the container services for message queue transport.
     * The method must return a connection service id.
     *
     * @param ContainerBuilder $container
     * @param array $config
     *
     * @return string
     */
    public function create(ContainerBuilder $container, array $config);

    /**
     * Defines the configuration key used as a reference to the transport configuration.
     *
     * @return string
     */
    public function getKey();

    /**
     * Add additional transport configuration information.
     *
     * @param NodeDefinition $builder
     *
     * @return void
     */
    public function addConfiguration(NodeDefinition $builder);
}
