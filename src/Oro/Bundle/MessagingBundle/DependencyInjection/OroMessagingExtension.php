<?php

namespace Oro\Bundle\MessagingBundle\DependencyInjection;

use Oro\Component\Messaging\Transport\Amqp\AmqpConnection;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession;
use Oro\Component\Messaging\Transport\Null\NullConnection;
use Oro\Component\Messaging\Transport\Null\NullSession;
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
        if (isset($config['transport']['null'])) {
            $connection = new Definition(NullConnection::class);
            $container->setDefinition('oro_messaging.transport.null.connection', $connection);

            $session = new Definition(NullSession::class);
            $session->setFactory([new Reference('oro_messaging.transport.null.connection'), 'createSession']);
            $container->setDefinition('oro_messaging.transport.null.session', $session);
            $defaultSessionId = 'oro_messaging.transport.null.session';
        } elseif (isset($config['transport']['amqp'])) {
            $amqpConfig = $config['transport']['amqp'];
            $connection = new Definition(AmqpConnection::class, [$amqpConfig]);
            $container->setDefinition('oro_messaging.transport.amqp.connection', $connection);

            $session = new Definition(AmqpSession::class);
            $session->setFactory([new Reference('oro_messaging.transport.amqp.connection'), 'createSession']);
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
