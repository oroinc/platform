<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory;

use Oro\Component\MessageQueue\Consumption\Dbal\DbalCliProcessManager;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalPidFileManager;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RedeliverOrphanMessagesDbalExtension;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RejectMessageOnExceptionDbalExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * This class configures the container services and describes additional
 * configuration for working with DBAL message queue transport.
 */
class DbalTransportFactory implements TransportFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, array $config)
    {
        $container->setParameter('oro_message_queue.dbal.pid_file_dir', $config['pid_file_dir']);
        $container->setParameter('oro_message_queue.dbal.connection', $config['connection']);
        $container->setParameter('oro_message_queue.dbal.table', $config['table']);
        $container->setParameter(
            'oro_message_queue.dbal.options',
            ['polling_interval' => $config['polling_interval']]
        );

        $pidFileManager = new Definition(DbalPidFileManager::class, [$config['pid_file_dir']]);
        $pidFileManagerId = sprintf('oro_message_queue.consumption.%s.pid_file_manager', $this->getKey());
        $container->setDefinition($pidFileManagerId, $pidFileManager);

        $cliProcessManager = new Definition(DbalCliProcessManager::class);
        $cliProcessManager->addMethodCall('setLogger', [new Reference('logger')]);
        $cliProcessManager->addTag('monolog.logger', ['channel' => 'consumer']);
        $cliProcessManagerId = sprintf('oro_message_queue.consumption.%s.cli_process_manager', $this->getKey());
        $container->setDefinition($cliProcessManagerId, $cliProcessManager);

        $orphanExtension = new Definition(RedeliverOrphanMessagesDbalExtension::class, [
            new Reference($pidFileManagerId),
            new Reference($cliProcessManagerId),
            $config['consumer_process_pattern'],
            new Expression("service('oro_message_queue.transport.parameters').getTransportName()"),
        ]);
        $orphanExtension->addTag('oro_message_queue.consumption.extension', ['priority' => -20]);
        $container->setDefinition(
            sprintf('oro_message_queue.consumption.%s.redeliver_orphan_messages_extension', $this->getKey()),
            $orphanExtension
        );

        $rejectOnExceptionExtension = new Definition(RejectMessageOnExceptionDbalExtension::class);
        $rejectOnExceptionExtension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition(
            sprintf('oro_message_queue.consumption.%s.reject_message_on_exception_extension', $this->getKey()),
            $rejectOnExceptionExtension
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'dbal';
    }

    /**
     * {@inheritdoc}
     *
     * @param ArrayNodeDefinition $builder
     */
    public function addConfiguration(NodeDefinition $builder)
    {
        $pidFileDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'oro-message-queue';

        $builder
            ->children()
                ->arrayNode($this->getKey())
                    ->addDefaultsIfNotSet()
                    ->info('DBAL transport configuration.')
                    ->children()
                        ->scalarNode('connection')
                            ->defaultValue('message_queue')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('table')
                            ->defaultValue('oro_message_queue')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('pid_file_dir')
                            ->defaultValue($pidFileDir)
                            ->cannotBeEmpty()
                        ->end()
                        ->integerNode('consumer_process_pattern')
                            ->defaultValue(':consume')
                        ->end()
                        ->integerNode('polling_interval')
                            ->min(50)
                            ->defaultValue(1000)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
