<?php
namespace Oro\Component\MessageQueue\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface TransportFactoryInterface
{
    /**
     * @param ArrayNodeDefinition $builder
     *
     * @return void
     */
    public function addConfiguration(ArrayNodeDefinition $builder);

    /**
     * @param ContainerBuilder $container
     * @param array $config
     *
     * @return string service id
     */
    public function createService(ContainerBuilder $container, array $config);

    /**
     * @return string
     */
    public function getName();
}
