<?php

namespace Oro\Bundle\MessagingBundle\DependencyInjection;

use Oro\Component\Messaging\Transport\Amqp\AmqpConnection;
use Oro\Component\Messaging\Transport\Null\NullConnection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroMessagingExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (isset($config['transport']['null']) && $config['transport']['null']) {
            $connection = new Definition(NullConnection::class);
            $container->setDefinition('oro_messaging.transport.null.connection', $connection);
        }

        if (isset($config['transport']['amqp']) && $config['transport']['amqp']) {
            $amqpConfig = $config['transport']['amqp'];
            $connection = new Definition(AmqpConnection::class, [$amqpConfig]);
            $connection->setFactory([AmqpConnection::class, 'createFromConfig']);
            $container->setDefinition('oro_messaging.transport.amqp.connection', $connection);
        }

        $defaultTransport = $config['transport']['default'];
        $container->setAlias(
            'oro_messaging.transport.connection',
            "oro_messaging.transport.$defaultTransport.connection"
        );

        if (isset($config['zeroconfig'])) {
            $loader->load('zeroconfig.yml');
            $this->loadZeroConfig($config, $container);
        }
    }

    protected function loadZeroConfig(array $config, ContainerBuilder $container)
    {
        $zeroconfig = $config['zeroconfig'];

        $container->setParameter('oro_messaging.zeroconfig.prefix', $zeroconfig['prefix']);
        $container->setParameter('oro_messaging.zeroconfig.router_topic', $zeroconfig['router_topic']);
        $container->setParameter('oro_messaging.zeroconfig.router_queue', $zeroconfig['router_queue']);
        $container->setParameter('oro_messaging.zeroconfig.queue_topic', $zeroconfig['queue_topic']);
        $container->setParameter('oro_messaging.zeroconfig.default_queue_queue', $zeroconfig['default_queue_queue']);
    }
}
