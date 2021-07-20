<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\TransportFactoryInterface;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment\TestBufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment\TestMessageBufferManager;
use Oro\Component\MessageQueue\Client\DbalDriver;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalLazyConnection;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroMessageQueueExtension extends Extension
{
    /** @var TransportFactoryInterface[] */
    private $factories = [];

    public function addTransportFactory(TransportFactoryInterface $transportFactory)
    {
        $this->factories[$transportFactory->getKey()] = $transportFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration($this->factories), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('log.yml');
        $loader->load('job.yml');
        $loader->load('commands.yml');

        if (isset($config['client'])) {
            $loader->load('client.yml');
            $loader->load('client_commands.yml');

            $driverFactory = $container->getDefinition('oro_message_queue.client.driver_factory');
            $driverFactory->replaceArgument(0, [
                DbalConnection::class => DbalDriver::class,
                DbalLazyConnection::class => DbalDriver::class,
            ]);

            $configDef = $container->getDefinition('oro_message_queue.client.config');
            $configDef->setArguments([
                $config['client']['prefix'],
                $config['client']['router_processor'],
                $config['client']['router_destination'],
                $config['client']['default_destination'],
                $config['client']['default_topic'],
            ]);

            if (!empty($config['client']['traceable_producer'])) {
                $producerId = 'oro_message_queue.client.traceable_message_producer';
                $container->register($producerId, TraceableMessageProducer::class)
                    ->setDecoratedService('oro_message_queue.client.message_producer')
                    ->addArgument(new Reference($producerId . '.inner'));
            }
        }

        if (isset($config['consumer'])) {
            $container->setParameter(
                'oro_message_queue.consumer_heartbeat_update_period',
                $config['consumer']['heartbeat_update_period']
            );
        }

        $this->createTransport($config, $container);
        $this->buildOptionalExtensions($config, $container);
        $this->setPersistenceServicesAndProcessors($config, $container);
        $this->setSecurityAgnosticTopicsAndProcessors($config, $container);
        $this->setJobConfigurationProvider($config, $container);

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
            $this->configureTestEnvironment($container);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return Configuration
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        $rc = new \ReflectionClass(Configuration::class);

        $container->addResource(new FileResource($rc->getFileName()));

        return new Configuration($this->factories);
    }

    private function createTransport(array $config, ContainerBuilder $container)
    {
        $transportKey = $container->getParameter('message_queue_transport');
        if (!$transportKey) {
            throw new InvalidConfigurationException('Message queue transport key is not defined.');
        }

        if (!array_key_exists($transportKey, $this->factories)) {
            throw new InvalidConfigurationException(
                sprintf('Message queue transport with key "%s" is not found.', $transportKey)
            );
        }

        $transportFactory = $this->factories[$transportKey];
        $connectionId = $transportFactory->create($container, $config['transport'][$transportKey]);

        $container->setAlias('oro_message_queue.transport.connection', $connectionId);
    }

    private function buildOptionalExtensions(array $config, ContainerBuilder $container)
    {
        if ($config['client']['redelivery']['enabled']) {
            $container->getDefinition('oro_message_queue.consumption.redelivery_message_extension')
                ->replaceArgument(1, $config['client']['redelivery']['delay_time'])
                ->addTag('oro_message_queue.consumption.extension');
        }

        // php pcntl extension available only for UNIX like systems
        if (extension_loaded('pcntl')) {
            $container->getDefinition('oro_message_queue.consumption.signal_extension')
                ->addTag('oro_message_queue.consumption.extension', ['persistent' => true]);
        }
    }

    /**
     * Sets the services that should not be reset during container reset and
     * Message Queue processors that can work without container reset to container reset extension.
     */
    private function setPersistenceServicesAndProcessors(array $config, ContainerBuilder $container)
    {
        if (!empty($config['persistent_services'])) {
            $container->getDefinition('oro_message_queue.consumption.container_clearer')
                ->addMethodCall('setPersistentServices', [$config['persistent_services']]);
        }
        if (!empty($config['persistent_processors'])) {
            $container->getDefinition('oro_message_queue.consumption.container_reset_extension')
                ->addMethodCall('setPersistentProcessors', [$config['persistent_processors']]);
        }
    }

    private function setSecurityAgnosticTopicsAndProcessors(array $config, ContainerBuilder $container)
    {
        if (!empty($config['security_agnostic_topics'])) {
            $container
                ->getDefinition('oro_message_queue.client.security_aware_driver_factory')
                ->replaceArgument(1, $config['security_agnostic_topics']);
        }
        if (!empty($config['security_agnostic_processors'])) {
            $container
                ->getDefinition('oro_message_queue.consumption.security_aware_extension')
                ->replaceArgument(0, $config['security_agnostic_processors']);
        }
    }

    private function setJobConfigurationProvider(array $config, ContainerBuilder $container)
    {
        if (!empty($config['time_before_stale'])) {
            $container->getDefinition('oro_message_queue.job.configuration_provider')
                ->addMethodCall('setConfiguration', [$config['time_before_stale']]);
        }
    }

    private function configureTestEnvironment(ContainerBuilder $container)
    {
        $container->getDefinition('oro_message_queue.client.buffered_message_producer')
            ->setClass(TestBufferedMessageProducer::class)
            ->setPublic(true);
        $container->getDefinition('oro_message_queue.client.message_buffer_manager')
            ->setClass(TestMessageBufferManager::class);
    }
}
