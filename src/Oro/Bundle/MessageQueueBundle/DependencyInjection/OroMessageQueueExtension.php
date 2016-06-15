<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection;

use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use OroPro\Component\MessageQueue\Transport\Amqp\AmqpConnection;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
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
            if (false == class_exists(AmqpConnection::class)) {
                throw new \LogicException('Amqp transport is not installed.');
            }

            $loader->load('amqp.yml');

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

        if (isset($config['client'])) {
            $loader->load('client.yml');

            $routerProcessorName = 'oro_message_queue.client.route_message_processor';

            $configDef = $container->getDefinition('oro_message_queue.client.config');
            $configDef->setArguments([
                $config['client']['prefix'],
                $config['client']['router_processor'] ?: $routerProcessorName,
                $config['client']['router_destination'],
                $config['client']['default_destination'],
            ]);

            if ($container->getParameter('kernel.debug')) {
                $container->setDefinition(
                    'oro_message_queue.client.internal_message_producer',
                    $container->getDefinition('oro_message_queue.client.message_producer')
                );

                $traceableMessageProducer = new Definition(TraceableMessageProducer::class, [
                    new Reference('oro_message_queue.client.internal_message_producer')
                ]);

                $container->setDefinition('oro_message_queue.client.message_producer', $traceableMessageProducer);
            }
        }
    }
}
