<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpConnection;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroMessageQueueExtension extends Extension
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
            $container->setDefinition('oro_message_queue.transport.null.connection', $connection);
        }

        if (isset($config['transport']['amqp']) && $config['transport']['amqp']) {
            $amqpConfig = $config['transport']['amqp'];
            $connection = new Definition(AmqpConnection::class, [$amqpConfig]);
            $connection->setFactory([AmqpConnection::class, 'createFromConfig']);
            $container->setDefinition('oro_message_queue.transport.amqp.connection', $connection);
        }

        $defaultTransport = $config['transport']['default'];
        $container->setAlias(
            'oro_message_queue.transport.connection',
            "oro_message_queue.transport.$defaultTransport.connection"
        );

        if (isset($config['zero_config'])) {
            $loader->load('zero_config.yml');

            $routerProcessorName = 'oro_message_queue.zero_config.route_message_processor';

            $configDef = $container->getDefinition('oro_message_queue.zero_config.config');
            $configDef->setArguments([
                $config['zero_config']['prefix'],
                $config['zero_config']['router_processor'] ?: $routerProcessorName,
                $config['zero_config']['router_destination'],
                $config['zero_config']['default_destination'],
            ]);
        }
    }
}
