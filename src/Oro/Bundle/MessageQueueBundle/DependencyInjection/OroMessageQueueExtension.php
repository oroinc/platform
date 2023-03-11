<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\TransportFactoryInterface;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment\TestBufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment\TestMessageBufferManager;
use Oro\Component\MessageQueue\Client\DbalDriver;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalLazyConnection;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroMessageQueueExtension extends Extension
{
    /** @var TransportFactoryInterface[] */
    private array $factories = [];

    public function addTransportFactory(TransportFactoryInterface $transportFactory): void
    {
        $this->factories[$transportFactory->getKey()] = $transportFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('log.yml');
        $loader->load('job.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('controllers_api.yml');
        $loader->load('mq_topics.yml');
        $loader->load('transport.yml');

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
                $config['client']['default_destination'],
                $config['client']['default_topic'],
            ]);

            if (!empty($config['client']['traceable_producer'])) {
                $producerId = 'oro_message_queue.client.traceable_message_producer';
                $container->register($producerId, TraceableMessageProducer::class)
                    ->setDecoratedService('oro_message_queue.client.message_producer')
                    ->addArgument(new Reference('.inner'));
            }

            $container->setParameter('oro_message_queue.client.noop_status', $config['client']['noop_status']);
        }

        if (isset($config['consumer'])) {
            $container->setParameter(
                'oro_message_queue.consumer_heartbeat_update_period',
                $config['consumer']['heartbeat_update_period']
            );
            $container->setParameter('oro_message_queue.client.noop_status', $config['client']['noop_status']);
        }

        $this->createTransports($config, $container);
        $this->buildOptionalExtensions($config, $container);
        $this->setPersistenceServicesAndProcessors($config, $container);
        $this->setSecurityAgnosticTopicsAndProcessors($config, $container);
        $this->setJobConfigurationProvider($config, $container);

        $container
            ->registerForAutoconfiguration(TopicInterface::class)
            ->addTag('oro_message_queue.topic');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
            $loader->load('mq_topics_test.yml');
            $this->configureTestEnvironment($container);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new Configuration($this->factories, $container->getParameter('kernel.environment'));
    }

    private function createTransports(array $config, ContainerBuilder $container): void
    {
        foreach ($this->factories as $transportKey => $transportFactory) {
            $transportFactory->create($container, $config['transport'][$transportKey]);
        }
    }

    private function buildOptionalExtensions(array $config, ContainerBuilder $container): void
    {
        if ($config['client']['redelivery']['enabled']) {
            $container->getDefinition('oro_message_queue.consumption.redelivery_message_extension')
                ->replaceArgument(1, $config['client']['redelivery']['delay_time'])
                // This extension should be called as early as possible as it rejects redelivered message,
                // so all extensions called before are useless.
                ->addTag('oro_message_queue.consumption.extension', ['priority' => 512]);
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
    private function setPersistenceServicesAndProcessors(array $config, ContainerBuilder $container): void
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

    private function setSecurityAgnosticTopicsAndProcessors(array $config, ContainerBuilder $container): void
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

    private function setJobConfigurationProvider(array $config, ContainerBuilder $container): void
    {
        if (!empty($config['time_before_stale'])) {
            $container->getDefinition('oro_message_queue.job.configuration_provider')
                ->addMethodCall('setConfiguration', [$config['time_before_stale']]);
        }
    }

    private function configureTestEnvironment(ContainerBuilder $container): void
    {
        $container->getDefinition('oro_message_queue.client.buffered_message_producer')
            ->setClass(TestBufferedMessageProducer::class)
            ->setPublic(true);
        $container->getDefinition('oro_message_queue.client.message_buffer_manager')
            ->setClass(TestMessageBufferManager::class);
    }
}
