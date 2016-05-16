<?php

namespace Oro\Bundle\MessagingBundle\DependencyInjection;

use Oro\Component\Messaging\Transport\Amqp\AmqpSession;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
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

        $defaultSessionId = null;
        if (isset($config['transport']['amqp'])) {
            $amqpConfig = $config['transport']['amqp'];
            $connection = new Definition(AMQPStreamConnection::class, [
                $amqpConfig['host'],
                $amqpConfig['port'],
                $amqpConfig['user'],
                $amqpConfig['pass'],
                $amqpConfig['vhost'],
            ]);
            $container->setDefinition('oro_messaging.transport.amqp.connection', $connection);
            
            $channel = new Definition(AMQPChannel::class);
            $channel->setFactory([new Reference('oro_messaging.transport.amqp.connection'), 'channel']);
            $container->setDefinition('oro_messaging.transport.amqp.channel', $channel);

            $session = new Definition(AmqpSession::class, [new Reference('oro_messaging.transport.amqp.channel')]);
            $container->setDefinition('oro_messaging.transport.amqp.session', $session);
            $defaultSessionId = 'oro_messaging.transport.amqp.session';
        }
        
        if ($defaultSessionId) {
            $container->setAlias('oro_messaging.transport.session', $defaultSessionId);
        } else {
            throw new \LogicException('Default transport is not configured.');
        }
    }
}
