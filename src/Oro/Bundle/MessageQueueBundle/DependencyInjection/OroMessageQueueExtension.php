<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection;

use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Oro\Component\MessageQueue\DependencyInjection\TransportFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroMessageQueueExtension extends Extension
{
    /**
     * @var TransportFactoryInterface[]
     */
    private $factories;

    public function __construct()
    {
        $this->factories = [];
    }

    /**
     * @param TransportFactoryInterface $transportFactory
     */
    public function addTransportFactory(TransportFactoryInterface $transportFactory)
    {
        $name = $transportFactory->getName();

        if (empty($name)) {
            throw new \LogicException('Transport factory name cannot be empty');
        }
        if (array_key_exists($name, $this->factories)) {
            throw new \LogicException(sprintf('Transport factory with such name already added. Name %s', $name));
        }

        $this->factories[$name] = $transportFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration($this->factories), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        
        foreach ($config['transport'] as $name => $transportConfig) {
            $this->factories[$name]->createService($container, $transportConfig);
        }

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

            if (false == empty($config['client']['traceable_producer'])) {
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
